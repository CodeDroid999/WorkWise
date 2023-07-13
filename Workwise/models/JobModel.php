<?php

namespace app\models;

use PDO;
use DateTime;
use PDOException;
use app\Database;
use app\utils\Logger;
use app\utils\DisplayAlert;

class JobModel extends _BaseModel
{

  private $db;

  private int $id;
  private int $client_id;
  private string $title;
  private string $description;
  private string $image;
  private float $pay_rate_per_hour;
  private float $expected_duration_in_hours;
  private string $receive_job_proposals_deadline;
  private string $time_created;
  private int $is_active;

  public function __construct(?int $id)
  {
    $this->db = $this->connectToDb();

    $sql = 'SELECT * FROM job WHERE id = :id';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $job = $statement->fetch();

    $this->id = $id;
    $this->client_id = $job['client_id'];
    $this->title = $job['title'];
    $this->description = $job['description'];
    $this->image = $job['image'];
    $this->pay_rate_per_hour = $job['pay_rate_per_hour'];
    $this->expected_duration_in_hours = $job['expected_duration_in_hours'];
    $this->receive_job_proposals_deadline = $job['receive_job_proposals_deadline'];
    $this->time_created = $job['time_created'];
    $this->is_active = $job['is_active'];
  }

  public static function tryGetById(int $id): ?JobModel
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT * FROM job WHERE id = :id';
    $statement = $db->prepare($sql);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $job = $statement->fetch();

    if ($job) {
      return new JobModel($job['id']);
    } else {
      DisplayAlert::displayError('job not found');
      return null;
    }
  }

  public static function create(
    int $client_id,
    string $title,
    string $description,
    string $image,
    float $pay_rate_per_hour,
    float $expected_duration_in_hours,
    string $receive_job_proposals_deadline,
  ): JobModel {

    $db = (new Database)->connectToDb();

    $sql = 'INSERT INTO job (client_id, title, description, image, pay_rate_per_hour, expected_duration_in_hours, receive_job_proposals_deadline, is_active) VALUES (:client_id, :title, :description, :image, :pay_rate_per_hour, :expected_duration_in_hours, :receive_job_proposals_deadline, 0)';
    $statement = $db->prepare($sql);
    $statement->bindParam(':client_id', $client_id);
    $statement->bindParam(':title', $title);
    $statement->bindParam(':description', $description);
    $statement->bindParam(':image', $image);
    $statement->bindParam(':pay_rate_per_hour', $pay_rate_per_hour);
    $statement->bindParam(':expected_duration_in_hours', $expected_duration_in_hours);
    $statement->bindParam(':receive_job_proposals_deadline', $receive_job_proposals_deadline);
    $statement->execute();

    $id = $db->lastInsertId();

    Logger::log("Job with id $id created");

    return new JobModel($id);
  }

  public function deactivate(): void
  {
    $sql = 'UPDATE job SET is_active = 0 WHERE id = :id';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':id', $this->id);
    $statement->execute();

    Logger::log("Job with id {$this->id} deactivated");
  }

  public function activate(): void
  {
    $sql = 'UPDATE job SET is_active = 1 WHERE id = :id';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':id', $this->id);
    $statement->execute();

    Logger::log("Job with id {$this->id} activated");
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function getClientId(): int
  {
    return $this->client_id;
  }

  public function getClient(): ClientModel
  {
    return new ClientModel($this->client_id);
  }

  public function getTitle(): string
  {
    return $this->title;
  }

  public function getDescription(): string
  {
    return $this->description;
  }

  public function getImage(): string
  {
    return $this->image;
  }

  public function getPayRatePerHour(): float
  {
    return $this->pay_rate_per_hour;
  }

  public function getExpectedDurationInHours(): float
  {
    return $this->expected_duration_in_hours;
  }

  public function getReceiveJobProposalsDeadline(): string
  {
    return $this->receive_job_proposals_deadline;
  }

  public function getTimeCreated(): string
  {
    return $this->time_created;
  }

  public function getIsActive(): int
  {
    return $this->is_active;
  }

  public function getBudget(): float
  {
    return $this->pay_rate_per_hour * $this->expected_duration_in_hours;
  }

  public function hasBeenPaidFor(): bool
  {
    if ($this->hasBeenRefunded()) {
      return false;
    }

    $sql = 'SELECT * FROM job_payment WHERE job_id = :job_id AND is_payment_successful = 1';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $job_payment = $statement->fetch();

    return $job_payment ? true : false;
  }

  public function getPayment(): ?JobPaymentModel
  {
    $sql = 'SELECT * FROM job_payment WHERE job_id = :job_id AND is_payment_successful = 1';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $job_payment = $statement->fetch();

    if ($job_payment) {
      return new JobPaymentModel($job_payment['id']);
    } else {
      return null;
    }
  }

  public function hasBeenRefunded(): bool
  {
    $payment = $this->getPayment();
    if ($payment) {
      return $payment->hasBeenRefunded();
    } else {
      return false;
    }
  }

  public function hasFreelancerBeenPaid(): bool
  {
    $payment = $this->getPayment();
    if ($payment) {
      return $payment->hasFreelancerBeenPaid();
    } else {
      return false;
    }
  }

  public function addSkills(array $skills): void
  {
    $sql = 'INSERT INTO job_skill (job_id, skill_id) VALUES (:job_id, :skill_id)';
    $statement = $this->db->prepare($sql);

    foreach ($skills as $skill) {
      try {
        $statement->bindParam(':job_id', $this->id);
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
   * @return JobModel[]
   */
  public static function getAll(int $limit = PHP_INT_MAX, int $offset = 0, array $skills = null, int $maxDuration = null, int $minDuration = null, int $maxPayRatePerHour = null, int $minPayRatePerHour = null,): array
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT id FROM job WHERE 1';
    if ($skills != null) {
      $sql .= " AND id in (SELECT job_id FROM job_skill WHERE skill_id IN (" . implode(',', $skills) . "))"; // should have at least one of the skills
    }
    if ($maxDuration != null) {
      $sql .= " AND expected_duration_in_hours <= $maxDuration";
    }
    if ($minDuration != null) {
      $sql .= " AND expected_duration_in_hours >= $minDuration";
    }
    if ($maxPayRatePerHour != null) {
      $sql .= " AND pay_rate_per_hour <= $maxPayRatePerHour";
    }
    if ($minPayRatePerHour != null) {
      $sql .= " AND pay_rate_per_hour >= $minPayRatePerHour";
    }
    $sql .= ' ORDER BY time_created DESC';
    $sql .= " LIMIT :limit OFFSET :offset"; // limit and offset for pagination

    $statement = $db->prepare($sql);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $jobs = $statement->fetchAll();

    $jobs_array = [];
    foreach ($jobs as $job) {
      $jobs_array[] = new JobModel($job['id']);
    }

    return $jobs_array;
  }

  public static function getAllCount(array $skills = null, int $maxDuration = null, int $minDuration = null, int $maxPayRatePerHour = null, int $minPayRatePerHour = null)
  {
    return count(self::getAll(PHP_INT_MAX, 0, $skills, $maxDuration, $minDuration, $maxPayRatePerHour, $minPayRatePerHour));
  }

  /**
   * Get all jobs open for proposals
   *
   * @return JobModel[]
   */
  public static function getAllOpenJobs(int $limit, int $offset, array $skills, int $maxDuration, int $minDuration, int $maxPayRatePerHour, int $minPayRatePerHour): array
  {
    $db = (new Database)->connectToDb();

    $now = (new DateTime())->format('Y-m-d H:i:s');

    $sql = 'SELECT * FROM job';
    $sql .= ' WHERE is_active = 1'; // must be active
    $sql .= ' AND receive_job_proposals_deadline > :now'; // deadline for receiving proposals should not have passed
    $sql .= " AND id NOT IN (SELECT job_id FROM job_proposal WHERE status IN ('" . implode("','", JobProposalModel::getAcceptedStatuses()) . "'))"; // should not have accepted a proposal
    $sql .= " AND id in (SELECT job_id FROM job_skill WHERE skill_id IN (" . implode(',', $skills) . "))"; // should have at least one of the skills
    $sql .= " AND expected_duration_in_hours <= :maxDuration";
    $sql .= " AND expected_duration_in_hours >= :minDuration";
    $sql .= " AND pay_rate_per_hour <= :maxPayRatePerHour";
    $sql .= " AND pay_rate_per_hour >= :minPayRatePerHour";
    $sql .= " ORDER BY receive_job_proposals_deadline ASC"; // order by nearest receive_job_proposals_deadline
    $sql .= " LIMIT :limit OFFSET :offset"; // limit and offset for pagination

    $statement = $db->prepare($sql);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
    $statement->bindParam(':now', $now);
    $statement->bindParam(':maxDuration', $maxDuration);
    $statement->bindParam(':minDuration', $minDuration);
    $statement->bindParam(':maxPayRatePerHour', $maxPayRatePerHour);
    $statement->bindParam(':minPayRatePerHour', $minPayRatePerHour);

    $statement->execute();
    $jobs = $statement->fetchAll();

    $jobModels = [];
    foreach ($jobs as $job) {
      $jobModels[] = new JobModel($job['id']);
    }

    return $jobModels;
  }

  /**
   * Returns the number of AllOpenJobs
   */
  public static function getAllOpenJobsCount(array $skills, int $maxDuration, int $minDuration, int $maxPayRatePerHour, int $minPayRatePerHour): int
  {
    return count(self::getAllOpenJobs(PHP_INT_MAX, 0, $skills, $maxDuration, $minDuration, $maxPayRatePerHour, $minPayRatePerHour));
  }

  /**
   * Gets the maximum duration of all jobs
   */
  public static function getJobsMaxDuration(): int
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT MAX(expected_duration_in_hours) FROM job';
    $sql .= ' WHERE is_active = 1'; // must be active
    $statement = $db->prepare($sql);
    $statement->execute();
    $result = $statement->fetch();

    if ($result && $result['MAX(expected_duration_in_hours)'] !== null) {
      return $result['MAX(expected_duration_in_hours)'];
    }

    return 0;
  }

  /**
   * Gets the maximum pay_rate_per_hour of all jobs
   */
  public static function getJobsMaxPayRatePerHour(): int
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT MAX(pay_rate_per_hour) FROM job';
    $sql .= ' WHERE is_active = 1'; // must be active
    $statement = $db->prepare($sql);
    $statement->execute();
    $result = $statement->fetch();

    if ($result && $result['MAX(pay_rate_per_hour)'] !== null) {
      return $result['MAX(pay_rate_per_hour)'];
    }

    return 0;
  }

  public function isJobCreatedByUser($userId): bool
  {
    $user = UserModel::tryGetById($userId);
    if ($user == null || $user->getClient() == null) {
      return false;
    }

    if (
      $this->getClientId() != $user->getClient()->getId()
    ) {
      return false;
    }

    return true;
  }

  public function isExpired(): bool
  {
    $now = new DateTime();
    $deadline = new DateTime($this->receive_job_proposals_deadline);

    return $now > $deadline;
  }

  public function getSkills(): array
  {
    $skills = array();

    $sql = 'SELECT skill_id FROM job_skill WHERE job_id = :id';
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':id', $this->id);
    $statement->execute();
    $job_skills = $statement->fetchAll();

    foreach ($job_skills as $job_skill) {
      array_push($skills, new SkillModel($job_skill['skill_id']));
    }

    return $skills;
  }

  /**
   * Get jobs that the client has created
   */
  public static function getClientJobs(int $clientId): array
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT * FROM job WHERE client_id = :client_id ORDER BY time_created DESC';
    $statement = $db->prepare($sql);
    $statement->bindParam(':client_id', $clientId);
    $statement->execute();
    $jobs = $statement->fetchAll();

    $jobModels = [];
    foreach ($jobs as $job) {
      $jobModels[] = new JobModel($job['id']);
    }

    return $jobModels;
  }

  /**
   * Get jobs that the freelancer has proposed to
   */
  public static function getFreelancerJobs(int $limit, int $offset, int $freelancerId, array $skills, int $maxDuration, int $minDuration, int $maxPayRatePerHour, int $minPayRatePerHour): array
  {
    $db = (new Database)->connectToDb();

    $sql = 'SELECT * FROM job WHERE id IN (SELECT job_id FROM job_proposal WHERE freelancer_id = :freelancer_id)';
    $sql .= " AND id in (SELECT job_id FROM job_skill WHERE skill_id IN (" . implode(',', $skills) . "))"; // should have at least one of the skills
    $sql .= " AND expected_duration_in_hours <= :maxDuration";
    $sql .= " AND expected_duration_in_hours >= :minDuration";
    $sql .= " AND pay_rate_per_hour <= :maxPayRatePerHour";
    $sql .= " AND pay_rate_per_hour >= :minPayRatePerHour";
    $sql .= " ORDER BY receive_job_proposals_deadline ASC"; // order by nearest receive_job_proposals_deadline
    $sql .= " LIMIT :limit OFFSET :offset"; // limit and offset for pagination

    $statement = $db->prepare($sql);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
    $statement->bindParam(':freelancer_id', $freelancerId);
    $statement->bindParam(':maxDuration', $maxDuration);
    $statement->bindParam(':minDuration', $minDuration);
    $statement->bindParam(':maxPayRatePerHour', $maxPayRatePerHour);
    $statement->bindParam(':minPayRatePerHour', $minPayRatePerHour);
    $statement->execute();
    $jobs = $statement->fetchAll();

    $jobModels = [];
    foreach ($jobs as $job) {
      $jobModels[] = new JobModel($job['id']);
    }

    return $jobModels;
  }

  /**
   * Get count of the jobs that the freelancer has proposed to
   */
  public static function getFreelancerJobsCount(int $freelancerId, array $skills, int $maxDuration, int $minDuration, int $maxPayRatePerHour, int $minPayRatePerHour): int
  {
    return count(self::getFreelancerJobs(PHP_INT_MAX, 0, $freelancerId, $skills, $maxDuration, $minDuration, $maxPayRatePerHour, $minPayRatePerHour));
  }

  public function getAcceptedProposal(): ?JobProposalModel
  {
    $sql = "SELECT * FROM job_proposal WHERE job_id = :job_id AND status IN ('" . implode("','", JobProposalModel::getAcceptedStatuses()) . "')";
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $proposal = $statement->fetch();

    if ($proposal) {
      return new JobProposalModel($proposal['id']);
    } else {
      return null;
    }
  }

  public function hasJobStarted(): bool
  {
    if ($this->getAcceptedProposal() != null) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if the job has been completed
   *
   * @return boolean
   */
  public function hasJobEnded(): bool
  {
    $sql = "SELECT * FROM job_proposal WHERE job_id = :job_id AND status IN ('" . implode("','", JobProposalModel::getCompletedStatuses()) . "')";
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $proposal = $statement->fetch();

    if ($proposal) {
      return true;
    } else {
      return false;
    }
  }

  public function hasReceivedProposals(): bool
  {
    $sql = "SELECT * FROM job_proposal WHERE job_id = :job_id";
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $proposal = $statement->fetch();

    if ($proposal) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if the freelancer (who's proposal was accepted) submitted work
   */
  public function hasWorkSubmitted(): bool
  {
    $sql = "SELECT * FROM job_proposal WHERE job_id = :job_id AND status IN ('" . implode("','", JobProposalModel::getWorkSubmittedStatuses()) . "')";;
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->execute();
    $proposal = $statement->fetch();

    if ($proposal) {
      return true;
    } else {
      return false;
    }
  }

  public function getFreelancerProposal(int $freelancerId): ?JobProposalModel
  {
    $sql = "SELECT * FROM job_proposal WHERE job_id = :job_id AND freelancer_id = :freelancer_id";
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':job_id', $this->id);
    $statement->bindParam(':freelancer_id', $freelancerId);
    $statement->execute();
    $proposal = $statement->fetch();

    if ($proposal) {
      return new JobProposalModel($proposal['id']);
    } else {
      return null;
    }
  }

  public function hasFreelancerCreatedProposal(int $freelancerId): bool
  {
    $proposal = $this->getFreelancerProposal($freelancerId);

    if ($proposal) {
      return true;
    } else {
      return false;
    }
  }

  public function isOpenForProposals(): bool
  {
    if ($this->is_active == 0 || $this->isExpired() || $this->hasJobStarted()) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Check if the freelancer was rated for this job
   */
  public function hasFreelancerRating(): bool
  {
    if (!$this->hasWorkSubmitted()) {
      return false;
    }

    return $this->getAcceptedProposal()->hasFreelancerRating();
  }

  /**
   * Check if the client was rated for this job
   */
  public function hasClientRating(): bool
  {
    if (!$this->hasWorkSubmitted()) {
      return false;
    }

    return $this->getAcceptedProposal()->hasClientRating();
  }

  public function isClientEligibleForRefund(): bool
  {
    $proposalValidForRefund = false;
    if (!$this->getAcceptedProposal() || !$this->getAcceptedProposal()->isClientEligibleForRefund()) {
      $proposalValidForRefund = true;
    }

    $jobValidForRefund = false;
    if ($this->is_active == 1 && ($this->isExpired() || !$this->hasJobStarted())) {
      $jobValidForRefund = true;
    }

    return $proposalValidForRefund && $jobValidForRefund;
  }
}