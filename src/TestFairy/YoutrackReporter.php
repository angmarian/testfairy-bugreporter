<?php

namespace Econtech\TestFairy;

use YouTrack\Connection as YouTrackConnection;

class YoutrackReporter implements \BugReporter
{

    protected $projectName;
    protected $projectId;
    protected $user;
    protected $password;
    protected $client;

    public function __construct($baseUrl, $username, $password, $projectName = null)
    {

        $this->baseUrl = $baseUrl;

        try {
            $this->client = new YouTrackConnection($baseUrl, $username, $password);
        } catch (\YouTrack\IncorrectLoginException $e) {
            error_log($e->getMessage());
            throw new \Exception($e->getMessage());
        }

        if ($projectName) {
            $this->projectName = $projectName;
            $this->projectId   = $this->getProjectIdByName($projectName);
        }

    }

    /**
     * Get type list
     *
     * @return array
     */
    public function getTypes()
    {
        return array(
            'Bug',
            'Cosmetics',
            'Exception',
            'Feature',
            'Task',
            'Usability Problem',
            'Performance Problem',
            'Epic',
            'Meta issue',
            'Auto-reported exception'
        );
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
        $issue = $this->client->createIssue($this->projectId, $title, array('description' => $body));
        return $issue->getAsArray();
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
        $issue = $this->getSummary(null, $id);
        $issue = $this->client->updateIssue($id, $issue['summary'], $body);
        return $issue->getAsArray();
    }

    /**
     * List all issues of this project in the bug system.
     *
     * @return array
     */
    public function getIssues()
    {
        $issues = $this->client->getIssues($this->projectId, null, null, 99999);
        return array_map(array($this, 'getIssueId'), $issues);
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
        $issue = $this->getSummary(null, $id);
        return (!empty($issue['State'])) ? $issue['State'] : null;
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
        return $this->client->getIssue($id)->getAsArray();
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
        return (!empty($issue['description'])) ? $issue['description'] : null;
    }

    /**
     * Get available projects.
     *
     * @return array
     */
    public function getProjects()
    {
        $projects = $this->client->getAccessibleProjects();
        return array_map(array($this, 'getProjectName'), $projects);
    }

    protected function getProjectIdByName($projectName)
    {
        foreach ($this->client->getAccessibleProjects() as $project) {
            if ($project->name == $projectName) {
                return $project->getShortName();
            }
        }

        error_log("Project not found.");
        throw new \Exception("Project not found.");

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
