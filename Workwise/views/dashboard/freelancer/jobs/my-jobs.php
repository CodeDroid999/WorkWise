<?php

$freelancer = app\models\UserModel::getCurrentUser()->getFreelancer();

?>


<!-------------------------------- intro -------------------------------------------------------->
<div class="container">
    <h1 style="text-align:center; margin-top:25px;">
        <?php
        if (isset($params) && isset($params['pageTitle'])) {
            echo $params['pageTitle'];
        } else {
            echo 'Top Jobs Right Now';
        }
        ?>
    </h1>

    <?php
    if (isset($params) && isset($params['pageSubTitle'])) {
        echo '<p style="text-align:center;">' . $params['pageSubTitle'] . '</p>';
    }
    ?>
</div>
<!-------------------------------- end intro -------------------------------------------------------->

<!-------------------------------- jobs list -------------------------------------------------------->
<div class="container" style="padding-bottom:5px; padding-top:10px; margin-bottom:10px">

    <!-------------------------------- filter -------------------------------------------------------->
    <hr style="margin: 1rem 0;" />
    <h3>Filter jobs</h3>
    <details>
        <summary>View Filters</summary>
        <form id="formID" action="/dashboard/freelancer/jobs/my-jobs" method="GET">
            <fieldset>

                <input hidden type="text" name="pageNumber" id="pageNumber" value="<?php echo $params['pageNumber']; ?>">

                <label for="skills[]">Skills <small>(Select multiple)</small></label>
                <select name="skills[]" id="skills[]" multiple size="10">
                    <?php foreach ($params["allSkills"] as $skill) { ?>
                        <option value="<?php echo $skill->getId(); ?>" <?php if (in_array($skill->getId(), $params['skills'])) { ?> selected <?php } ?>>
                            <?php echo $skill->getName(); ?>
                        </option>
                    <?php } ?>
                </select>
                <span class="invalidFeedback">
                    <?php echo $params["skillsError"]; ?>
                </span>

                <label for="maxDuration">Max Duration <small>(hours)</small></label>
                <input type="text" name="maxDuration" id="maxDuration" value="<?php echo $params['maxDuration']; ?>">
                <span class="invalidFeedback">
                    <?php echo $params['maxDurationError']; ?>
                </span>

                <label for="minDuration">Min Duration <small>(hours)</small></label>
                <input type="text" name="minDuration" id="minDuration" value="<?php echo $params['minDuration']; ?>">
                <span class="invalidFeedback">
                    <?php echo $params['minDurationError']; ?>
                </span>

                <label for="maxPayRatePerHour">Max Pay Rate Per Hour <small>(KES)</small></label>
                <input type="text" name="maxPayRatePerHour" id="maxPayRatePerHour" value="<?php echo $params['maxPayRatePerHour']; ?>">
                <span class="invalidFeedback">
                    <?php echo $params['maxPayRatePerHourError']; ?>
                </span>

                <label for="minPayRatePerHour">Min Pay Rate Per Hour <small>(KES)</small></label>
                <input type="text" name="minPayRatePerHour" id="minPayRatePerHour" value="<?php echo $params['minPayRatePerHour']; ?>">
                <span class="invalidFeedback">
                    <?php echo $params['minPayRatePerHourError']; ?>
                </span>

                <hr style="margin: 1rem 0;" />

                <input class="button-primary" type="submit" value="Submit">
            </fieldset>
        </form>

        <a href="/dashboard/freelancer/jobs/my-jobs?pageNumber=<?php echo $params['pageNumber']; ?>">
            <input class="button-primary" type="submit" value="Reset Filters">
        </a>

    </details>
    <!-------------------------------- end filter -------------------------------------------------------->


    <hr style="margin: 1rem 0;" />
</div>

<div class="container" style="margin-top:25px;">

    <?php foreach ($params["jobs"] as $job) { ?>
        <!-------------------------------- job -------------------------------------------------------->
        <div class="container rounded-corners background-color-gray" style="padding-bottom:5px; padding-top:10px; margin-bottom:10px">
            <div class="row" style="justify-content:space-between;">
                <div class="column">
                    <h3 style=" margin:auto 0px;" class="center-text-on-small-screen">
                        <?php echo $job->getTitle(); ?>
                    </h3>
                </div>
                <div class="column">
                    <p class="center-text-on-small-screen" style="text-align:right;">
                        <b>Client:</b>
                        <a href="/dashboard/freelancer/clients/id?clientId=<?php echo $job->getClient()->getId(); ?>">
                            <?php echo $job->getClient()->getTitle(); ?>
                        </a>
                    </p>
                </div>
            </div>
            <hr style="margin: 1rem 0;" />
            <div class="row">
                <p style="text-align:left; margin:auto 0px;">
                    <?php echo $job->getDescription(); ?>
                </p>
            </div>
            <hr style="margin: 1rem 0;" />
            <div class="row" style="justify-content:space-between;">
                <div class="column">
                    <p class="center-text-on-small-screen" style="text-align:left; margin:auto 0px;">
                        <b>Proposal deadline:</b>
                        <script type="text/javascript">
                            formatDateToHumanCalendar("<?php echo $job->getReceiveJobProposalsDeadline() ?>");
                        </script>
                    </p>
                </div>
                <div class="column">
                    <p style="text-align:center; margin:auto 0px;">
                        <b>Duration:</b>
                        <?php echo $job->getExpectedDurationInHours(); ?> hours
                    </p>
                </div>
                <div class="column">
                    <p class="center-text-on-small-screen" style="text-align:right;">
                        <b>Date posted:</b>
                        <script type="text/javascript">
                            formatDateToHumanCalendar("<?php echo $job->getTimeCreated(); ?>");
                        </script>
                    </p>
                </div>
            </div>
            <div class="row" style="justify-content:space-between;">
                <div class="column">
                    <p class="center-text-on-small-screen" style="text-align:left; margin:auto 0px;">
                        <b>Is open for proposals: </b>
                        <?php
                        if ($job->isOpenForProposals()) {
                            echo '&#9989;';
                        } else {
                            echo "&#10060;";
                        }
                        ?>
                    </p>
                </div>
                <div class="column">
                    <p style="text-align:center; margin:auto 0px;">

                    </p>
                </div>
                <div class="column">
                    <p class="center-text-on-small-screen" style="text-align:right;">
                        <b>Given Proposal: </b>
                        <?php
                        if ($job->hasFreelancerCreatedProposal($freelancer->getId())) {
                            echo '&#9989;';
                        } else {
                            echo "&#10060;";
                        }
                        ?>
                    </p>
                </div>
            </div>
            <div class="row" style="justify-content:space-between;">
                <div class="column" style="margin-bottom:5px;">
                    <p class="center-text-on-small-screen" style="text-align:left; margin:auto 0px;">
                        <b>Budget:</b>
                        KES
                        <?php echo $job->getBudget() ?>
                    </p>
                </div>
                <div class="column" style="margin-bottom:5px;">
                    <p style="text-align:center; margin:auto 0px;">
                        <b>Skills: </b>
                        <?php foreach ($job->getSkills() as $skill) {
                            echo "#" . $skill->getName() . "  ";
                        } ?>
                    </p>
                </div>
                <div class="column" style="margin-bottom:5px;">
                    <a href="/dashboard/freelancer/jobs/id?jobId=<?php echo $job->getId() ?>">
                        <button class=" center-self-on-screen float-right-on-large-screen ">
                            View &rarr;
                        </button>
                    </a>
                </div>
            </div>
        </div>
        <!-------------------------------- end job -------------------------------------------------------->
    <?php } ?>

    <!-------------------------------- pagination -------------------------------------------------------->
    <div class="pagination">
        <a onClick="changeInputValueAndSubmitForm('formID', 'pageNumber', 1)">First</a>
        <?php if ($params['previousPageNumber'] > 0) { ?>
            <a onClick="changeInputValueAndSubmitForm('formID', 'pageNumber', <?php echo $params['previousPageNumber']; ?> )">
                &laquo;&laquo;
            </a>
        <?php } ?>
        <a onClick="javascript:void(0)" class="active"><?php echo $params['pageNumber']; ?></a>
        <?php if ($params['nextPageNumber'] <= $params['lastPageNumber']) { ?>
            <a onClick="changeInputValueAndSubmitForm('formID', 'pageNumber', <?php echo $params['nextPageNumber']; ?> )">
                &raquo;&raquo;
            </a>
        <?php } ?>
        <a onClick="changeInputValueAndSubmitForm('formID', 'pageNumber', <?php echo $params['lastPageNumber']; ?> )">
            Last
        </a>
        <p style="text-align:right;"><small><?php echo $params['recordsCount'] ?> items</small></p>
    </div>
    <!-------------------------------- end pagination -------------------------------------------------------->
</div>
<!-------------------------------- end jobs list -------------------------------------------------------->