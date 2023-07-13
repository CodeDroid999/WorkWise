<?php

namespace app\models;

use PDO;
use PDOException;
use app\Database;
use app\utils\Logger;
use app\utils\DisplayAlert;


class FreelancerModel extends _BaseModel
{
    private $db;

    private int $id;
    private string $title;
    private string $description;
    private int $user_id;
    private int $years_of_experience;
    private $time_created;
    private int $is_active;
    private ?string $national_id;

    public function __construct(int $id)
    {
        $this->db = $this->connectToDb();

        $sql = 'SELECT * FROM freelancer WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->execute();
        $freelancer_s = $statement->fetch();

        $this->id = $id;
        $this->title = $freelancer_s['title'];
        $this->description = $freelancer_s['description'];
        $this->user_id = $freelancer_s['user_id'];
        $this->years_of_experience = $freelancer_s['years_of_experience'];
        $this->time_created = $freelancer_s['time_created'];
        $this->is_active = $freelancer_s['is_active'];
        $this->national_id = $freelancer_s['national_id'];
    }

    public static function tryGetById($freelancerId): ?FreelancerModel
    {
        $db = (new Database)->connectToDb();

        $sql = 'SELECT * FROM freelancer WHERE id = :id';
        $statement = $db->prepare($sql);
        $statement->bindParam(':id', $freelancerId);
        $statement->execute();
        $freelancer = $statement->fetch();

        if ($freelancer) {
            return new FreelancerModel($freelancerId);
        } else {
            DisplayAlert::displayError('Freelancer not found');
            return null;
        }
    }

    public static function create(
        string $title,
        string $description,
        int $user_id,
        int $years_of_experience,
        string $national_id
    ): ?FreelancerModel {
        $db = (new Database)->connectToDb();

        $user = UserModel::tryGetById($user_id);
        if (!$user) {
            DisplayAlert::displayError('User not found.');
            return null;
        } else if ($user->isClient()) {
            DisplayAlert::displayError('User is already a client.');
            return null;
        } else if ($user->getIsAdmin()) {
            DisplayAlert::displayError('Admin cannot be a freelancer.');
            return null;
        }

        $sql = 'INSERT INTO freelancer (title, description, user_id, years_of_experience, national_id) VALUES (:title, :description, :user_id, :years_of_experience, :national_id)';
        $statement = $db->prepare($sql);
        $statement->bindParam(':title', $title);
        $statement->bindParam(':description', $description);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':years_of_experience', $years_of_experience);
        $statement->bindParam(':national_id', $national_id);
        $statement->execute();

        $id = $db->lastInsertId();

        Logger::log("Freelancer with id $id has been created for user with id $user_id", $user_id, 'Create Freelancer');

        return new FreelancerModel($id);
    }

    public function update(
        string $title,
        string $description,
        int $years_of_experience,
    ): ?FreelancerModel {

        $sql = 'UPDATE freelancer SET title = :title, description = :description, years_of_experience = :years_of_experience WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':title', $title);
        $statement->bindParam(':description', $description);
        $statement->bindParam(':years_of_experience', $years_of_experience);
        $statement->bindParam(':id', $this->id);
        $statement->execute();

        Logger::log("Freelancer with id {$this->id} has been updated", $this->user_id);

        return new FreelancerModel($this->id);
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getTitle(): mixed
    {
        return $this->title;
    }
    public function getDescription(): mixed
    {
        return $this->description;
    }

    public function getUserId(): mixed
    {
        return $this->user_id;
    }

    public function getYearsOfExperience(): mixed
    {
        return $this->years_of_experience;
    }

    public function getTimeCreated(): mixed
    {
        return $this->time_created;
    }

    public function getIsActive(): mixed
    {
        return $this->is_active;
    }

    public function getNationalId(): mixed
    {
        return $this->national_id;
    }

    public function getUser(): UserModel
    {
        return new UserModel($this->user_id);
    }

    public function getSkills(): array
    {
        $skills = array();

        $sql = 'SELECT skill_id FROM freelancer_skill WHERE freelancer_id = :id';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $this->id);
        $statement->execute();
        $freelancer_skills = $statement->fetchAll();
        foreach ($freelancer_skills as $freelancer_skill) {
            array_push($skills, new SkillModel($freelancer_skill['skill_id']));
        }

        return $skills;
    }

    public function addSkills(array $skills): void
    {
        // delete prev skills
        $sql = 'DELETE FROM freelancer_skill WHERE freelancer_id = :id';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $this->id);
        $statement->execute();

        // add new skills
        $sql = 'INSERT INTO freelancer_skill (freelancer_id, skill_id) VALUES (:freelancer_id, :skill_id)';
        $statement = $this->db->prepare($sql);

        foreach ($skills as $skill) {
            try {
                $statement->bindParam(':freelancer_id', $this->id);
                $statement->bindParam(':skill_id', $skill);
                $statement->execute();
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    // duplicate entry
                    continue;
                } else {
                    // other error. Throw it
                    throw $e;
                }
            }
        }
    }

    /**
     * @return FreelancerModel[]
     */
    public static function getAll(int $limit = PHP_INT_MAX, int $offset = 0, array $skills = null,): array
    {
        $db = (new Database)->connectToDb();

        $sql = 'SELECT id FROM freelancer';
        if ($skills !== null) {
            $sql .= ' WHERE id IN (SELECT freelancer_id FROM freelancer_skill WHERE skill_id IN (' . implode(',', $skills) . '))';
        }
        $sql .= ' ORDER BY time_created DESC';
        $sql .= " LIMIT :limit OFFSET :offset"; // limit and offset for pagination

        $statement = $db->prepare($sql);
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $freelancers = $statement->fetchAll();

        $freelancers_array = [];
        foreach ($freelancers as $freelancer) {
            $freelancers_array[] = new FreelancerModel($freelancer['id']);
        }

        return $freelancers_array;
    }

    public static function getAllCount(array $skills = null)
    {
        return count(FreelancerModel::getAll(PHP_INT_MAX, 0, $skills));
    }

    /**
     * Get the average of all ratings for this freelancer
     *
     * @return float
     */
    public function getAverageRating(): float
    {
        /**
         * SQL:
         * 1. get all proposals (that are completed successfully) by freelancer 'SELECT job_id FROM job_proposal WHERE freelancer_id = :id AND status = "completed successfully"'
         * 2. get all jobs or this freelancer by checking the job_ids in the proposals 'SELECT id FROM job WHERE id IN (the above proposals)'
         * 3. get the average of all ratings for the above jobs 'SELECT AVG(rating) FROM job_rating WHERE type = "freelancer" AND job_id IN (the above jobs)'
         */
        $sql = 'SELECT AVG(rating) FROM job_rating WHERE type = "freelancer" AND job_id IN';
        $sql .= ' (SELECT id FROM job WHERE id IN';
        $sql .= '   (SELECT job_id FROM job_proposal WHERE freelancer_id = :id AND status = "completed successfully")';
        $sql .= ' )';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $this->id);
        $statement->execute();
        $averageRating = $statement->fetch();

        if ($averageRating && $averageRating['AVG(rating)'] !== null) {
            return round($averageRating['AVG(rating)'], 1);
        }

        return 0.0;
    }

    public function getAverageRatingImage(): string
    {
        return JobRatingModel::getImageForRatingInt($this->getAverageRating());
    }

    /**
     * Get all ratings given to the freelancer.
     * 
     * @return JobRatingModel[]
     */
    public function getAllRatings(): array
    {
        /**
         * SQL:
         * 1. get all proposals (that are completed successfully) by freelancer 'SELECT job_id FROM job_proposal WHERE freelancer_id = :id AND status = "completed successfully"'
         * 2. get all jobs or this freelancer by checking the job_ids in the proposals 'SELECT id FROM job WHERE id IN (the above proposals)'
         * 3. get all ratings for the above jobs 'SELECT id FROM job_rating WHERE type = "freelancer" AND job_id IN (the above jobs)'
         */
        $sql = 'SELECT id FROM job_rating WHERE type = "freelancer" AND job_id IN';
        $sql .= ' (SELECT id FROM job WHERE id IN';
        $sql .= '   (SELECT job_id FROM job_proposal WHERE freelancer_id = :id AND status = "completed successfully")';
        $sql .= ' )';
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $this->id);
        $statement->execute();
        $ratings = $statement->fetchAll();

        $ratings_array = [];
        foreach ($ratings as $rating) {
            $ratings_array[] = new JobRatingModel($rating['id']);
        }

        return $ratings_array;
    }
}