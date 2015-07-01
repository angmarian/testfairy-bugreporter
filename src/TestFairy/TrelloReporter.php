<?php

namespace Econtech\TestFairy;

use Trello\Client;
use Trello\Manager;
use Trello\Service;

class TrelloReporter implements \BugReporter
{

    protected $client;
    protected $manager;

    protected $boardId;
    protected $boardName;

    protected $key;

    protected $authorizeUrl = 'https://trello.com/1/authorize?key=%s&name=TestFairy&expiration=never&response_type=token&scope=read,write';

    public function __construct($key, $token = null, $boardName = null)
    {

        $this->key = $key;

        $this->client = new Client();

        try {
            $this->client->authenticate($key, $token, Client::AUTH_URL_CLIENT_ID);

            $this->manager = new Manager($this->client);

            if ($boardName) {
                $this->boardName = $boardName;
                $this->boardId   = $this->getProjectIdByName($boardName);
            }
        } catch (\Trello\Exception\RuntimeException $e) {
            error_log($e->getMessage());
            throw new \Exception($e->getMessage());
        }

    }

    public function getAuthorizeUrl()
    {
        return sprintf($this->authorizeUrl, $this->key);
    }

    /**
     * Get type list
     *
     * @return array
     */
    public function getTypes()
    {
        return array('card');
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

        $lists = $this->manager->getBoard($this->boardId)->getLists();

        if (!empty($lists)) {
            $currentList = array_shift($lists);
        } else {
            return false;
        }

        $member = $this->manager->getMember('me')->getData();

        $card = $this->client->api('card')->create(array(
                    'name'   => $title,
                    'desc'   => $body,
                    'idList' => $currentList->getId(),
                    'idMembers' => $member['id'],
                ));

        return $card;
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
        $this->manager->getCard($id)
             ->setDescription($body)
             ->save();

    }

    /**
     * List all issues of this project in the bug system.
     *
     * @return array
     */
    public function getIssues()
    {
        $issues = array();

        foreach ($this->manager->getBoard($this->boardId)->getLists() as $list) {
            $issues = array_merge($issues, $list->getCards());
        }

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

        $issue = $this->manager->getCard($id);

        return $issue->getList()->getName();

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
        return $this->manager->getCard($id)->getData();
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
        return $this->manager->getCard($id)->getDescription();
    }

    /**
     * Get available projects.
     *
     * @return array
     */
    public function getProjects()
    {
        $boards = $this->client->api('member')->boards()->all('me');

        if (empty($boards)) {
            error_log("No projects found.");
            throw new \Exception("No projects found.");
        }

        return array_map(array($this, 'getProjectName'), $boards);
    }

    public function getLists()
    {
        $lists = $this->manager->getBoard($this->boardId)->getLists();
        return array_map(array($this, 'getListName'), $lists);
    }

    protected function getProjectIdByName($projectName)
    {
        foreach ($this->client->api('member')->boards()->all('me') as $project) {
            if ($project['name'] == $projectName) {
                return $project['id'];
            }
        }

        error_log("Project not found.");
        throw new \Exception("Project not found.");

        return false;
    }

    private static function getProjectName($project)
    {
        return $project['name'];
    }

    private static function getIssueId($issue)
    {
        return $issue->getId();
    }

    private static function getListName($list)
    {
        return $list->getName();
    }
}
