<?php if ($params && isset($params['job'])) {
    $job = $params['job'];

    $freelancer = app\models\UserModel::getCurrentUser()->getFreelancer();

    $proposal = $job->getFreelancerProposal($freelancer->getId());
?>
    <!-------------------------------- intro -------------------------------------------------------->
    <div class="container">
        <h1 style="text-align:center; margin-top:25px;">
            <?php echo $job->getTitle(); ?>
        </h1>
    </div>
    <!-------------------------------- end intro -------------------------------------------------------->


    <!-------------------------------- job -------------------------------------------------------->
    <div class="container rounded-corners background-color-gray" style="padding-bottom:5px; padding-top:10px; margin-bottom:10px">
        <h2 style="text-align:left; margin-top:25px;">
            Job Details
        </h2>
        <hr style="margin: 1rem 0;" />
        <div class="row">
            <p style="text-align:left; margin:auto 0px;">
                <?php echo $job->getDescription(); ?>
            </p>

            <?php if ($job->getImage()) { ?>
                <img class="quarter-screen-size-image" src="<?php echo $job->getImage(); ?>" class="center-on-small-screen container" />
            <?php } ?>

        </div>

        <hr />

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
                <p class="center-text-on-small-screen" style="text-align:right;">
                    <b>Client:</b>
                    <a href="/dashboard/freelancer/clients/id?clientId=<?php echo $job->getClient()->getId(); ?>">
                        <?php echo $job->getClient()->getTitle(); ?>
                    </a>
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
                    <?php if ($job->hasFreelancerCreatedProposal($freelancer->getId())) { ?>
                        <b>Given proposal: </b>
                        <?php
                        if ($job->hasFreelancerCreatedProposal($freelancer->getId())) {
                            echo '&#9989;';
                        } else {
                            echo "&#10060;";
                        }
                        ?>
                    <?php } ?>
                </p>
            </div>
            <div class="column">
                <p class="center-text-on-small-screen" style="text-align:right;">
                    <?php if (!$job->hasFreelancerCreatedProposal($freelancer->getId())) { ?>
                        <b>Given proposal: </b>
                        <?php if ($job->hasFreelancerCreatedProposal($freelancer->getId())) {
                            echo '&#9989;';
                        } else {
                            echo "&#10060;";
                        }
                        ?>
                    <?php } else { ?>
                        <b>Is proposal accepted: </b>
                        <?php
                        if ($job->getFreelancerProposal($freelancer->getId())->isProposalAccepted()) {
                            echo '&#9989;';
                        } else {
                            echo "&#10060;";
                        }
                        ?>

                    <?php } ?>
                </p>
            </div>
        </div>
        <div class="row" style="justify-content:space-between;">
            <div class="column">
                <p class="center-text-on-small-screen" style="text-align:left; margin:auto 0px;">
                    <b>Has started: </b>
                    <?php
                    if ($job->hasJobStarted()) {
                        echo '&#9989;';
                    } else {
                        echo "&#10060;";
                    }
                    ?>
                </p>
            </div>
            <div class="column">
                <p style="text-align:center; margin:auto 0px;">
                    <b>Has work submitted: </b>
                    <?php
                    if ($job->hasWorkSubmitted()) {
                        echo '&#9989;';
                    } else {
                        echo "&#10060;";
                    }
                    ?>
            </div>
            <div class="column">
                <p class="center-text-on-small-screen" style="text-align:right;">
                    <b>Has ended: </b>
                    <?php
                    if ($job->hasJobEnded()) {
                        echo '&#9989;';
                    } else {
                        echo "&#10060;";
                    }
                    ?>
                </p>
                </p>
            </div>
        </div>

        <hr style="margin: 1rem 0;" />

        <a href="/dashboard/freelancer/jobs/id/proposal?jobId=<?php echo $job->getId() ?>">
            <button class="">
                Proposal &rarr;
            </button>
        </a>

        <hr style="margin: 1rem 0;" />

        <?php if ($proposal != null && $proposal->isProposalAccepted()) { ?>
            <a href="/dashboard/freelancer/jobs/id/submit-work?jobId=<?php echo $job->getId() ?>">
                <button class="">
                    Submission/Rating &rarr;
                </button>
            </a>
        <?php } ?>

    </div>
    <!-------------------------------- end job -------------------------------------------------------->

<?php

} else {
    echo "Job details not found";
}

?>