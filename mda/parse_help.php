<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<!-- Modal dialog for Metasys File help -->
<div class="modal fade" id="helpInputFile" tabindex="-1" role="dialog" aria-labelledby="helpMetasysFileLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpMetasysFileLabel"><?=METASYS_DATA_ANALYSIS?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dd>
            A <i><?=METASYS_FILE?></i> is a .csv file exported from Metasys.
          </dd>
        </dl>
        <dl>
          <dd>
            <ol>
              <li>
                Choose to analyze a <?=METASYS_FILE?> or to plot results from a past analysis.
              </li>
              <li>
                Select a file.
              </li>
              <li>
                Click <i>Submit</i>.
              </li>
            </ol>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal dialog for Analysis Options help -->
<div class="modal fade" id="helpOptions" tabindex="-1" role="dialog" aria-labelledby="helpOptionsLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpOptionsLabel"><?=ANALYSIS_OPTIONS?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dt>
            <?=REPORT_FORMAT?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                <?=SUMMARY?>
              </dt>
              <dd>
                Aggregates results in specified <?=TIME_PERIOD?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=DETAILED?>
              </dt>
              <dd>
                Includes a result for each distinct timestamp, optionally within specified <?=TIME_PERIOD?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=MULTIPLE?>
              </dt>
              <dd>
                Runs a predetermined series of analyses.
              </dd>
            </dl>
          </dd>
        </dl>
        <dl>
          <dt>
            <?=TIME_PERIOD?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                <?=FULL_DAY?>
              </dt>
              <dd>
                <ul>
                  <li>
                    For <i><?=SUMMARY?></i> <?=REPORT_FORMAT?>, aggregates results in 24-hour periods beginning with <i><?=START_TIME?></i>.
                  </li>
                  <li>
                    For <i><?=DETAILED?></i> <?=REPORT_FORMAT?>, includes a result for each distinct timestamp in <?=METASYS_FILE?>.
                  </li>
                </ul>
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=PARTIAL_DAY?>
              </dt>
              <dd>
                Analyzes data in periods from <i><?=START_TIME?></i> to <i><?=END_TIME?></i>.
              </dd>
              <dd>
                <ul>
                  <li>
                    If <i><?=START_TIME?></i> is greater than <i><?=END_TIME?></i>, the periods cross midnight.
                  </li>
                  <li>
                    <i><?=START_TIME?></i> and <i><?=END_TIME?></i> must have different values.
                  </li>
                </ul>
              </dd>
            </dl>
          </dd>
        </dl>
        <dl>
          <dt>
            <?=START_TIME?>
          </dt>
          <dt>
            <?=END_TIME?>
          </dt>
          <dd>
            <ul>
              <li>
                Click to open the Time Editor.
              </li>
              <li>
                Use mouse or arrow keys to edit time.
              </li>
            </ul>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal dialog for Columns help -->
<div class="modal fade" id="helpColumns" tabindex="-1" role="dialog" aria-labelledby="helpColumnsLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpColumnsLabel"><?=POINTS_OF_INTEREST?></h4>
      </div>
      <div class="modal-body bg-info">

        <dl>
          <dt>
            Available <?=POINTS_OF_INTEREST?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                Default
              </dt>
              <dd>
                Selects default <?=POINTS_OF_INTEREST?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                All
              </dt>
              <dd>
                Selects all <?=POINTS_OF_INTEREST?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                None
              </dt>
              <dd>
                Deselects all <?=POINTS_OF_INTEREST?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                Complement
              </dt>
              <dd>
                Reverses current <?=POINTS_OF_INTEREST?> selections.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                Search...
              </dt>
              <dd>
                Selects all <?=POINTS_OF_INTEREST?> containing a match.
              </dd>
            </dl>
          </dd>
        </dl>


        <dl>
          <dt>
            Selected <?=POINTS_OF_INTEREST?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                Nickname
              </dt>
              <dd>
                Replaces <?=POINT_OF_INTEREST?> name in <?=RESULTS_FILE?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                 Move Up (<span class="glyphicon glyphicon-menu-up"></span>)
              </dt>
              <dt>
                 Move Down (<span class="glyphicon glyphicon-menu-down"></span>)
              </dt>
              <dt>
                Drag <?=POINT_OF_INTEREST?>
              </dt>
              <dd>
                Changes column order in <?=RESULTS_FILE?>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                Remove (<span style="font-size:20px" >&times;</span>)
              </dt>
              <dd>
                Deselects <?=POINT_OF_INTEREST?>.
              </dd>
            </dl>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal dialog for Plot help -->
<div class="modal fade" id="helpPlot" tabindex="-1" role="dialog" aria-labelledby="helpPlotLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpPlotLabel"><?=METASYS_DATA_ANALYSIS_RESULTS?></h4>
      </div>
      <div class="modal-body bg-info">

        <dl>
          <dt>
            <?=POINTS_OF_INTEREST?>
          </dt>
          <dd>
            Use checkboxes and and buttons to select for display in plot.
          </dd>
        </dl>

        <dl>
          <dt>
            Plot
          </dt>
          <dd>
            <ul>
              <li>
                Drag mouse across plot to select range.
              </li>
              <li>
                Click plot to deselect range.
              </li>
              <li>
                Use scrollbar to pan selected range.
              </li>
              <li>
                Use buttons below plot to zoom in and out of selected range.
              </li>
              <li>
                Use checkboxes below plot to customize display.
              </li>
              <li>
                Hover mouse over plot to view interpolated values in legend.
              </li>
              <li>
                Hover mouse over plot points to view sample values tooltips.
              </li>
              <li>
                Enter dollars per unit below plot to show cost in tooltips and legend.
              </li>
            </ul>
          </dd>
        </dl>

        <br/>
        <h4>Advanced</h4>

        <dl>
          <dt>
            Down Sample by Density
          </dt>
          <dd>
            <ul>
              <li>
                <b>Density</b>: Maximum number of samples to show, evenly distributed throughout range
              </li>
              <li>
                <b>Offset</b>: Index of first sample
              </li>
            </ul>
          </dd>
        </dl>

        <dl>
          <dt>
            Down Sample by Pattern
          </dt>
          <dd>
            <ul>
              <li>
                <b>Show</b>: Number of samples to show in alternating pattern
              </li>
              <li>
                <b>Hide</b>: Number of samples to hide in alternating pattern
              </li>
              <li>
                <b>Offset</b>: Index of first sample
              </li>
            </ul>
          </dd>
        </dl>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
