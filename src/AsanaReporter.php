<?php


class AsanaReporter implements BugReporter
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

        $this->asana = new Asana(array(
            'apiKey' => $key,
        ));

        $this->user = json_decode($this->asana->getUserInfo())->data;

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
        $issue = json_decode($this->asana->createTask(array(
           'projects'  => "{$this->projectId}",
           'name'      => $title,
           'notes'     => $body,
           'workspace' => "{$this->workspaceId}",
           'assignee'  => "{$this->user->id}",
        )), true);

        if (!empty($issue['data'])) { return $issue['data']; }

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
        return json_decode($this->asana->updateTask($id, array(
            'notes' => $body,
        )))->data;
    }

    /**
     * List all issues of this project in the bug system.
     *
     * @return array
     */
    public function getIssues()
    {
        return array_map(function ($issue) {
            return $issue->id;
        }, json_decode($this->asana->getTasksByFilter(array('project' => $this->projectId)))->data);
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
        $issue = json_decode($this->asana->getTask($id))->data;

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
        $response = json_decode($this->asana->getTask($id), true);
        return $response['data'];
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
        return array_map(function ($project) {
            return $project->name;
        }, json_decode($this->asana->getProjects())->data);
    }

    protected function getProjectIdByName($projectName)
    {
        foreach (json_decode($this->asana->getProjects())->data as $project) {
            if ($project->name == $projectName) {
                return $project->id;
            }
        }
        return false;
    }

}
