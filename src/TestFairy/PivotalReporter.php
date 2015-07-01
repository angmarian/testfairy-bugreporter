<?php

namespace Econtech\TestFairy;

use PivotalTrackerV5\Client as PivotalClient;

class PivotalReporter implements \BugReporter
{

    protected $client;

    public function __construct($token, $projectName = null)
    {

        $this->client = new PivotalClient($token);

        if ($projectName) {
            try {
                $projectId = $this->client->getProjectIdByName($projectName);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

            $this->client->setProject($projectId);
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
            "feature", "bug", "chore", "release"
        );
    }

    /**
     * Creates a new issue with the given title and description.
     *
     * @param $title
     * @param $description
     * @return array
     */
    public function createIssue($title, $body)
    {
        return $this->client->addStory(array(
            'name' => $title,
            'description' => $body,
        ));
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
        return $this->client->updateStory($id, array(
            'description' => $body,
        ));
    }

    /**
     * List all issues of this project in the bug system.
     *
     * @return array
     */
    public function getIssues()
    {
        $issues = $this->client->getStories();

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
        if (!empty($issue['current_state'])) {
            return $issue['current_state'];
        }
        return false;
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
        return $this->client->getStory($id);
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
        if (!empty($issue['description'])) {
            return $issue['description'];
        }
        return false;
    }

    /**
     * Get available projects.
     *
     * @return array
     */
    public function getProjects()
    {
        $projects = $this->client->getProjects();

        return array_map(array($this, 'getProjectName'), $data);
    }

    private static function getProjectName($project)
    {
        return $project['name'];
    }

    private static function getIssueId($issue)
    {
        return $issue['id'];
    }
}
