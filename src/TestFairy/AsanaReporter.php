<?php

namespace Econtech\TestFairy;

class AsanaReporter implements \BugReporter
{

    protected $key;
    protected $asana;
    protected $projectName;
    protected $projectId;
    protected $workspaceId;
    protected $user;

    public function __construct($key, $projectName = null)
    {

        $this->key = $key;

        $this->asana = new \Asana(array(
            'apiKey' => $key,
        ));

        if (!$this->asana) {
            error_log("Cannot connect to ASANA.");
            throw new \Exception("Cannot connect to ASANA.");
        }

        $userData = $this->asana->getUserInfo();

        if (!$userData) {
            error_log("Cannot fetch user data. Maybe apiKey is wrong?");
            throw new \Exception("Cannot fetch user data. Maybe apiKey is wrong?");
        }

        $this->user = json_decode($userData)->data;

        if ($projectName) {
            $this->projectName = $projectName;
            $this->projectId   = $this->getProjectIdByName($projectName);
            $this->workspaceId = json_decode($this->asana->getProject($this->projectId))->data->workspace->id;
        }

    }

    /**
     * Get type list
     *
     * @return array
     */
    public function getTypes()
    {
        return array('task');
    }

    /**
     * Creates a new issue with the given title and description.
     *
     * @param $summary
     * @param $description
     * @return array
     */
    public function createIssue($title, $body)
    {
        $task = $this->asana->createTask(array(
           'projects'  => "{$this->projectId}",
           'name'      => $title,
           'notes'     => $body,
           'workspace' => "{$this->workspaceId}",
           'assignee'  => "{$this->user->id}",
        ));

        $issue = json_decode($task, true);

        if (!empty($issue['data'])) {
            return $issue['data'];
        }

        return false;
    }

    /**
     * Update an issue with a new description.
     *
     * @param $key
     * @param $id
     * @param $description
     * @return boolean
     */
    public function editIssue($key, $id, $body)
    {
        $task = $this->asana->updateTask($id, array(
            'notes' => $body,
        ));

        return json_decode($task)->data;
    }

    /**
     * List all issues of this project in the bug system.
     *
     * @return array
     */
    public function getIssues()
    {
        $issues = $this->asana->getTasksByFilter(array('project' => $this->projectId));
        $data   = json_decode($issues)->data;

        return array_map(array($this, "getIssueId"), $data);
    }

    /**
     * Get issue endpoint url
     *
     * @param IssueWrapper $issue
     * @return string
     */
    public function getIssueEndpointUrl(IssueWrapper $issue)
    {

    }

    /**
     * Returns the status of a specific issue.
     *
     * @param $issueKey
     * @param $id
     * @return string
     */
    public function getStatus($key, $id)
    {
        $task  = $this->asana->getTask($id);
        $issue = json_decode($task)->data;

        if ($issue->completed) {
            return 'Complete';
        } else {
            return 'Incomplete';
        }
    }

    /**
     * Returns the summary of a specific issue.
     *
     * @param $issueKey
     * @param $id
     * @return string
     */
    public function getSummary($key, $id)
    {
        $task  = $this->asana->getTask($id);
        $issue = json_decode($task, true);

        return $issue['data'];
    }

    /**
     * Returns the description of a specific issue.
     *
     * @param $key
     * @param $id
     * @return string
     */
    public function getDescription($key, $id)
    {
        $issue = $this->getSummary(null, $id);

        return $issue['notes'];
    }

    /**
     * Get available projects.
     *
     * @return array
     */
    public function getProjects()
    {
        $projects = $this->asana->getProjects();
        $data     = json_decode($projects)->data;

        if (empty($data)) {
            error_log("No projects found.");
            throw new \Exception("No projects found.");
        }

        return array_map(array($this, "getProjectName"), $data);
    }

    protected function getProjectIdByName($projectName)
    {
        $projects = $this->asana->getProjects();
        $data     = json_decode($projects)->data;

        foreach ($data as $project) {
            if ($project->name == $projectName) {
                return $project->id;
            }
        }

        error_log('Cannot found project with name "' . $projectName . '".');
        throw new \Exception('Cannot found project with name "' . $projectName . '".');

        return false;
    }

    private static function getProjectName($project)
    {
        return $project->name;
    }

    private static function getIssueId($issue)
    {
        return $issue->id;
    }
}
