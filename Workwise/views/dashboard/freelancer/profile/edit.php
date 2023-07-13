<div class="container">
    <h1 style="text-align:center; margin:auto 0px;">Edit freelancer profile</h1>
    <hr />
    <form action="/dashboard/freelancer/profile/edit" method="POST" enctype="multipart/form-data">
        <fieldset>
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo $params["title"]; ?>">
            <span class="invalidFeedback">
                <?php echo $params["titleError"]; ?>
            </span>

            <label for="years_of_experience">Years of experience</label>
            <input type="text" name="years_of_experience" id="years_of_experience" value="<?php echo $params["years_of_experience"]; ?>">
            <span class="invalidFeedback">
                <?php echo $params["years_of_experienceError"]; ?>
            </span>

            <label for="description">Description</label>
            <textarea type="text" name="description" id="description" rows="4">
            <?php echo $params["description"]; ?>
            </textarea>
            <span class="invalidFeedback">
                <?php echo $params["descriptionError"]; ?>
            </span>

            <label for="skills[]">Skills</label>
            <select name="skills[]" id="skills[]" multiple>
                <?php foreach ($params["allSkills"] as $skill) { ?>
                    <option <?php if (in_array($skill->getId(), $params['skills'])) { ?> selected <?php } ?> value="<?php echo $skill->getId(); ?>">
                        <?php echo $skill->getName(); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="invalidFeedback">
                <?php echo $params["skillsError"]; ?>
            </span>

            <hr style="margin: 1rem 0;" />

            <input class="button-primary" type="submit" value="Submit">
        </fieldset>
    </form>
</div>