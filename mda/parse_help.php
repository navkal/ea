<!-- Modal dialog for Metasys File help -->
<div class="modal fade" id="helpMetasysFile" tabindex="-1" role="dialog" aria-labelledby="helpMetasysFileLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpMetasysFileLabel"><?=METASYS_FILE?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dd>
            Select a .csv file exported from Metasys and click <i>Submit</i>.
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
