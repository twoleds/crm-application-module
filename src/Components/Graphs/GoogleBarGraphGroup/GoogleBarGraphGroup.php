<?php

namespace Crm\ApplicationModule\Components\Graphs;

use Crm\ApplicationModule\Graphs\GraphData;
use Nette\Application\UI\Multiplier;
use Nette\Utils\DateTime;

/**
 * Google bar graph group component
 *
 * Component for rendering bar graph using google graph library
 * capable of rendering multiple graphs depending on selected group by.
 *
 * @package Crm\ApplicationModule\Components\Graphs
 */
class GoogleBarGraphGroup extends BaseGraphControl
{
    private $view = 'google_bar_graph_group';

    private $yLabel = '[Y label]';

    private $xLabel = '[X label]';

    private $xAxis = [];

    private $graphTitle = '[Názov grafu]';

    private $graphHelp = 'help';

    private $height = 300;

    private $seriesName = '';

    private $stacked = true;

    /** @var callable */
    private $serieTitleCallback;

    private $start = ['day' => '-60 days', 'week' => '-8 weeks', 'month' => '-4 months', 'year' => '-3 years'];

    /** @var GoogleBarGraphControlFactoryInterface */
    private $googleBarGraphFactory;

    /** @var GraphData */
    private $graphData;

    private $from;

    private $to;

    public function __construct(GoogleBarGraphControlFactoryInterface $factory, GraphData $graphData)
    {
        $this->googleBarGraphFactory = $factory;
        $graphData->clear();
        $this->graphData = $graphData;
    }

    public function setStacked($stacked)
    {
        $this->stacked = $stacked;
        return $this;
    }

    public function setYLabel($yLabel)
    {
        $this->yLabel = $yLabel;
        return $this;
    }

    public function setXLabel($xLabel)
    {
        $this->xLabel = $xLabel;
        return $this;
    }

    public function setGraphTitle($graphTitle)
    {
        $this->graphTitle = $graphTitle;
        return $this;
    }

    public function setGraphHelp($graphHelp)
    {
        $this->graphHelp = $graphHelp;
        return $this;
    }

    public function setSerieTitleCallback($serieTitleCallback)
    {
        $this->serieTitleCallback = $serieTitleCallback;
        return $this;
    }

    public function addSerie($name, $data)
    {
        $this->series[$name] = $data;
        if (!$this->xAxis) {
            $this->xAxis = array_keys($data);
        }
        return $this;
    }

    public function setSeries($seriesName)
    {
        $this->seriesName = $seriesName;
        return $this;
    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    public function render($asyncLoad = true)
    {
        if ($asyncLoad && !$this->getPresenter()->isAjax()) {
            $this->graphData->clear();
        }
        $this->template->graphId = $this->generateGraphId();
        $this->template->graphTitle = $this->graphTitle;
        $this->template->graphHelp = $this->graphHelp;
        $this->template->xAxis = $this->xAxis;
        $this->template->yLabel = $this->yLabel;
        $this->template->series = $this->series;
        $this->template->height = $this->height;
        $this->template->range = $this->getParameter('range');
        $this->template->groupBy = $this->getParameter('groupBy', 'day');
        $this->template->from = $this->getParameter('from', $this->from);
        $this->template->to = $this->getParameter('to', $this->to);
        $this->template->asyncLoad = $asyncLoad;

        $this->template->setFile(__DIR__ . '/' . $this->view . '.latte');
        $this->template->render();
    }

    public function addGraphDataItem($graphDataItem)
    {
        $this->graphData->addGraphDataItem($graphDataItem);
        return $this;
    }

    public function createComponentGraph()
    {
        return new Multiplier(function ($groupBy) {
            $control = $this->googleBarGraphFactory->create();
            $control->setYLabel($this->yLabel)
                ->setStacked($this->stacked)
                ->setGraphTitle($this->graphTitle);

            $this->graphData->setScaleRange($groupBy);

            $from = $this->getParameter('from');
            $to = $this->getParameter('to');
            $range = $this->getParameter('range');

            if ($range) {
                $this->graphData->setStart($range);
                $this->graphData->setEnd(DateTime::from('today')->format('Y-m-d'));
            } elseif ($from || $to) {
                $this->graphData->setStart($from);
                $this->graphData->setEnd($to);
            } else {
                $this->graphData->setStart($this->start['day']);
                $this->graphData->setEnd(DateTime::from('today')->format('Y-m-d'));
            }

            foreach ($this->graphData->getSeriesData() as $k => $v) {
                if ($this->serieTitleCallback) {
                    $k = ($this->serieTitleCallback)($k);
                }
                $control->addSerie($k, $v);
            }

            return $control;
        });
    }

    public function handleChange($range, $groupBy, $from = null, $to = null)
    {
        $this->template->range = $range;
        $this->template->groupBy = $groupBy;
        $this->template->from = $from;
        $this->template->to = $to;
        $this->template->redraw = true;
        $this->redrawControl('ajaxChange');
    }
}
