<?php

/**
 * Tool to reload the entire local database from remote database for a given site
 * Requirements:
 * - ssh access to remote server (via ~/.ssh/config)
 * - both local and remote sites must be accesible via: /sites/MY_SITE
 * - both local and remote config/autoload/local.php files must contains the database password
 * - local database must be configured with ~/.pgpass file for prompt-free access (could be automated)
 */
abstract class AbstractDatabase extends Task
{

    /**
     * Execute PHP code on $remote server
     * @param string $php code (without special escaping, nor '<?php')
     */
    private static function executeRemotePhp($remote, $php)
    {
        // Create temp file with PHP code
        $tempFile = '/tmp/gims.remotephp.' . exec('whoami') . '.php';
        $php = "<?php " . $php . "\n //unlink('$tempFile');";
        file_put_contents($tempFile, $php);

        // Push temp file on remote and delete local copy
        self::executeLocalCommand("scp $tempFile $remote:$tempFile");
        unlink($tempFile);

        // Execute remote code (who will delete itself)
        $sshCmd = <<<STRING
        ssh $remote "php $tempFile"
STRING;
        self::executeLocalCommand($sshCmd);
    }

    /**
     * Dump data from database on $remote server
     * @param string $remote
     * @param string $dumpFile path
     */
    private static function dumpDataRemotely($remote, $dumpFile)
    {
        $remoteCode = <<<STRING
        require_once('/sites/$remote/config/autoload/local.php');
        \$pgpass = trim(`echo ~`) . "/.pgpass";
        file_put_contents(\$pgpass, "localhost:5432:\$database:\$username:\$password");
        chmod(\$pgpass, 0600);

        \$dumpCmd = "pg_dump --host localhost --username \$username --format=custom \$database | gzip > $dumpFile";
        exec(\$dumpCmd);
STRING;

        echo "dumping data $dumpFile on $remote...\n";
        self::executeRemotePhp($remote, $remoteCode);
    }

    /**
     * Copy a file from $remote
     * @param string $remote
     * @param string $dumpFile
     */
    private static function copyFile($remote, $dumpFile)
    {
        $copyCmd = <<<STRING
        scp $remote:$dumpFile $dumpFile
STRING;

        echo "copying dump to $dumpFile ...\n";
        self::executeLocalCommand($copyCmd);
    }

    /**
     * Load SQL dump in local database
     * @param string $siteLocal
     * @param string $dumpFile
     */
    public static function loadDump($siteLocal, $dumpFile)
    {
        $config = require("$siteLocal/config/autoload/local.php");
        $dbConfig = $config['doctrine']['connection']['orm_default']['params'];
        $username = $dbConfig['user'];
        $database = $dbConfig['dbname'];

        echo "loading dump $dumpFile...\n";
        self::executeLocalCommand('./vendor/bin/doctrine-module orm:schema-tool:drop --full-database --force');
        self::executeLocalCommand('./vendor/bin/doctrine-module dbal:run-sql "DROP TYPE IF EXISTS questionnaire_status CASCADE;"');
        self::executeLocalCommand('./vendor/bin/doctrine-module dbal:run-sql "DROP RULE IF EXISTS geometry_columns_delete ON geometry_columns CASCADE;"');
        self::executeLocalCommand('./vendor/bin/doctrine-module dbal:run-sql "DROP RULE IF EXISTS geometry_columns_insert ON geometry_columns CASCADE;"');
        self::executeLocalCommand('./vendor/bin/doctrine-module dbal:run-sql "DROP RULE IF EXISTS geometry_columns_update ON geometry_columns CASCADE;"');
        self::executeLocalCommand("gunzip -c $dumpFile | pg_restore --host localhost --username $username --no-owner --dbname=$database");
        self::executeLocalCommand('./vendor/bin/doctrine-module migrations:migrate --no-interaction');
    }

    public static function loadRemoteDump($remote)
    {
        $siteLocal = trim(`git rev-parse --show-toplevel`);

        $dumpFile = "/tmp/$remote." . exec("whoami") . ".backup.gz";
        self::dumpDataRemotely($remote, $dumpFile);
        self::copyFile($remote, $dumpFile);
        self::loadDump($siteLocal, $dumpFile);

        echo "database updated\n";
    }

    /**
     * Execute a shell command and throw exception if fails
     * @param string $command
     * @throws \Exception
     */
    protected static function executeLocalCommand($command)
    {
        $return_var = null;
        $fullCommand = "$command 2>&1";
        passthru($fullCommand, $return_var);
        if ($return_var) {
            throw new \Exception('FAILED executing: ' . $command);
        }
    }

}