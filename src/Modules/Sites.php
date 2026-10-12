<?php
namespace Pkiwi\Modules;

use Pkiwi\Api\MatomoClient;
use Pkiwi\Database\Connection;

class Sites
{
    /** @var Connection|null */
    private $db;
    /** @var MatomoClient */
    private $matomo;

    /**
     * @param Connection|null $db
     * @param MatomoClient $matomo
     */
    public function __construct(Connection $db = null, MatomoClient $matomo)
    {
        $this->db = $db;
        $this->matomo = $matomo;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getSites($userId)
    {
        return $this->db->query(
            'SELECT * FROM pkiwi_user_sites WHERE user_id = ? ORDER BY created_at ASC',
            [(int)$userId]
        );
    }

    /**
     * @param int $userId
     * @param int $id
     * @return array|null
     */
    public function getSite($userId, $id)
    {
        $site = $this->db->queryOne(
            'SELECT * FROM pkiwi_user_sites WHERE id = ? AND user_id = ?',
            [(int)$id, (int)$userId]
        );
        return $site ?: null;
    }

    /**
     * Add a website: registers it in Matomo (analytics server) and links it to the user
     *
     * @param int $userId
     * @param string $name
     * @param string $url
     * @return array ['ok' => bool, 'error' => string|null, 'site' => array|null]
     */
    public function addSite($userId, $name, $url)
    {
        $name = trim((string)$name);
        $url = trim((string)$url);

        if ($name === '') {
            return ['ok' => false, 'error' => 'Please enter a website name.', 'site' => null];
        }
        if (!preg_match('#^https?://.+#i', $url)) {
            $url = 'http://' . $url;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'Invalid website address.', 'site' => null];
        }

        // Already registered for this user?
        $existing = $this->db->queryOne(
            'SELECT * FROM pkiwi_user_sites WHERE user_id = ? AND url = ?',
            [(int)$userId, $url]
        );
        if ($existing) {
            return ['ok' => false, 'error' => 'This website is already in your list.', 'site' => null];
        }

        // Register the site on the analytics (Matomo) server
        try {
            $matomoSiteId = $this->matomo->addSite($name, $url, [
                'siteSearch' => 1,
                'searchKeywordParameters' => 'motCle',
            ]);
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => 'Analytics server error: ' . $e->getMessage(), 'site' => null];
        }

        if (empty($matomoSiteId)) {
            return ['ok' => false, 'error' => 'The analytics server did not return a site ID.', 'site' => null];
        }

        $matomoSiteId = (int)$matomoSiteId;

        $this->db->execute(
            'INSERT INTO pkiwi_user_sites (user_id, matomo_site_id, name, url, created_at) VALUES (?, ?, ?, ?, NOW())',
            [(int)$userId, $matomoSiteId, $name, $url]
        );

        $id = (int)$this->db->lastInsertId();
        $site = $this->getSite($userId, $id);

        return ['ok' => true, 'error' => null, 'site' => $site];
    }

    /**
     * Remove a website from the user's list (keeps the site in Matomo)
     *
     * @param int $userId
     * @param int $id
     * @return bool
     */
    public function removeSite($userId, $id)
    {
        $this->db->execute(
            'DELETE FROM pkiwi_user_sites WHERE id = ? AND user_id = ?',
            [(int)$id, (int)$userId]
        );
        return true;
    }
}
