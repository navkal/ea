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
                Aggregates results in specified time periods.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=DETAILED?>
              </dt>
              <dd>
                Includes a result for each distinct timestamp found in the Metasys File.
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
                Aggregates results in 24-hour periods beginning with <i><?=START_TIME?></i>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=PARTIAL_DAY?>
              </dt>
              <dd>
                Aggregates results in periods from <i><?=START_TIME?></i> to <i><?=END_TIME?></i>.
              </dd>
              <dd>
                <ul>
                  <li>
                    If <i><?=START_TIME?></i> is greater than <i><?=END_TIME?></i>, the time periods cross midnight.
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
            <?=POINTS_OF_INTEREST?>
          </dt>
          <dd>
            <ol>
              <li>
                blah blah blah
              </li>
              <li>
                foo moo goo
              </li>
              <li>
                and other stuff
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
