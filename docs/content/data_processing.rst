Data visualisation and analysis
===============================

Also GIMS has been built in such a way as to be able to handle numerous
different data sets, the initial efforts have been focused on the processing
of :term:`JMP` data as will be explained below in the :ref:`JMPgraphAnalysis`

Until the other interfaces are programmed, users can already use GIMS for the
:doc:`data collection process <data_input>` and then **export** the answers
in the form of Excel spreadsheets for further analysis. This can be done in
two different places that correspond to different process stages:

* **Export questionnaire answers**: this is done from the Admin / Surveys
  where you can either :guilabel:`Export all questionnaires` (button at the top)
  or :guilabel:`Export selected` questionnaires (use the check boxes to select
  the countries). Data exported corresponds to the "raw", *non-processed* data.

.. image:: img/questionnaire_export.png
    :width: 100%
    :alt: Exporting questionnaire content

* **Export specific filtered data**: this can be done currently from the
  Browse / **Questionnaires** and **Countries** tables.

.. image:: img/browse.png
    :width: 100%
    :alt: Browse

In these tables, select the country(ies), :term:`filter set(s)<filter set>`
(+ years) and once the content is visible, simply click on the :guilabel:`Export`
button. The file you will obtain is already *processed* data through the
:term:`filters<filter>` and their associated :term:`rules<rule>`.

.. note::

    The "Questionnaires table" provides an output of the filtered data per
    survey for the selected country(ies).

.. image:: img/data_export_questionnaire1.png
    :width: 100%
    :alt: Exporting questionnaire filtered data

If you wish to get specific information, you can select it via the "Filters" tab [1]. To select a filter, click on the button [2] and in the following interface, choose the filter(s) you would like to display.

.. image:: img/data_export_questionnaire2.png
    :width: 100%
    :alt: Selecting filters

.. note::

    The "Countries table" provides an output of computed data, in absolute (A)
    and relative numbers, per year for the selected countries. To know what the
    acronyms mean, hover over the label.

.. image:: img/data_export_countries.png
    :width: 100%
    :alt: Exporting countries filtered data


.. _JMPgraphAnalysis:

JMP graphs and graphical analysis
---------------------------------

To see how the water supply or sanitation facilities coverage evolve over
time, trend lines are calculated based on a selection of data points. This
interface is accessible via  Browse / Charts.

.. image:: img/browse.png
    :width: 100%
    :alt: Browse

Select a country [1], part (urban, rural or total) [2] and filter set [3].
The corresponding data points and trends lines are calculated and
displayed. Pointing anywhere along the trend line provides a feedback
regarding the corresponding coverage percentage [4].

.. image:: img/chart_1.png
    :width: 100%
    :alt: Chart with trend lines

Estimates can also be seen in the form of a table by clicking on the
"Estimates" tab.

.. image:: img/chart_2.png
    :width: 100%
    :alt: Estimated coverage

Relative trend bars can also be obtained and will display the selected filters. By hovering over the graph, detailed percentage will show up for the start and
end years (1990 and 2015).

.. image:: img/chart_3.png
    :width: 100%
    :alt: Relative trend bars

To get detailed information regarding a specific data point (questionnaire),
you must simply click on the point. Since data points can overlap, you can
zoom into the chart by drawing a rectangle over the area of interest (click
and drag in diagonal).

.. image:: img/chart_zoom.png
    :width: 100%
    :alt: Zooming into the chart

Once you have zoomed in, you can select more easily the data point you are
interested in [1].

.. image:: img/chart_zoomed.png
    :width: 100%
    :alt: Zoom of the chart

.. note::

    To view again the entire graph, click on the :guilabel:`Reset zoom` button [2].

The panel on the right opens up and displays detailed information pertaining
to the data point selected. The first tab (Filters) shows the details of what
makes up this data point.

.. image:: img/chart_panel1.png
    :width: 100%
    :alt: Chart information panel (filters)

.. _DP graphical analysis:

Graphical analysis
^^^^^^^^^^^^^^^^^^

To see the impact of ignoring a specific filter either just for the current
questionnaire [1] or for all questionnaires displayed [2], click on the
corresponding icon. The data points (questionnaires) are recalculated
without this filter element and a new trend line calculated and drawn. The
initial data points and trend lines are now in a lighter shade of the initial colour (as shown in the legend). Furthermore, the percentage
ignored is highlighted in red [3] and the (sub)total(s) adjusted accordingly
[4]. It is possible to ignore several elements by simply clicking on the
appropriate icons of the filter elements you wish to ignore.

.. note::

    By clicking on the labels in the legend [5], you can hide elements you don't
    want to display on the graph.

.. image:: img/chart_panel2.png
    :width: 100%
    :alt: Ignoring a specific filter for a graph

The ignored data can be seen in the "Ignored" tab and reintegrated by simply
clicking on the appropriate icon, depending on what you wish to take into account.

.. image:: img/chart_panel3.png
    :width: 100%
    :alt: Ignored elements

It is also possible to ignore an entire questionnaire by clicking on the
icon to the left of the questionnaire code [1]. The data point of the
questionnaire is shaded on the chart [2] as well as the label in the panel and
the trend line recalculated accordingly. To reintegrating a questionnaire,
click again on the same icon.

.. image:: img/chart_panel4.png
    :width: 100%
    :alt: Ignore an entire questionnaire

Certain data points of a given filter are either entirely of partly the result
of calculations. This is shown by the presence of the cogged-wheel icon [1].

.. note::

    It is possible to access the details of the calculations by clicking on
    the rule label [2]. This will forward you to the :ref:`rule-edition`
    section.

.. image:: img/chart_panel5.png
    :width: 100%
    :alt: Calculated filter values

For further analysis, see the :doc:`JMP data reconciliation process
<data_reconciliation>`.

.. note::

    Need further help? Send an email to who@gimsinitiative.org