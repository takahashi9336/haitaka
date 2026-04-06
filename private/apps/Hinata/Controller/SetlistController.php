<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\EventModel;
use App\Hinata\Model\EventShadowNarrationModel;
use App\Hinata\Model\MemberModel;
use App\Hinata\Model\SetlistModel;
use App\Hinata\Model\SongModel;
use Core\Auth;

/**
 * LIVEセットリスト表示・編集コントローラ
 */
class SetlistController {
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function show(): void {
        $this->auth->requireLogin();

        $eventId = (int)($_GET['event_id'] ?? 0);
        if ($eventId === 0) {
            header('Location: /hinata/events.php');
            exit;
        }

        $eventModel = new EventModel();
        $event = $eventModel->find($eventId);
        if (!$event) {
            header('Location: /hinata/events.php');
            exit;
        }

        $setlistModel = new SetlistModel();
        $setlist = $setlistModel->getByEventId($eventId);

        $shadowModel = new EventShadowNarrationModel();
        $shadow = $shadowModel->getByEventId($eventId);
        $shadowMemberIds = $shadow ? ($shadow['member_ids'] ?? []) : [];

        $shadowMembers = [];
        if (!empty($shadowMemberIds)) {
            $memberModel = new MemberModel();
            $all = $memberModel->getActiveMembersWithColors();
            $byId = [];
            foreach ($all as $m) $byId[(int)$m['id']] = $m;
            foreach ($shadowMemberIds as $mid) {
                $m = $byId[(int)$mid] ?? null;
                if ($m) $shadowMembers[] = ['id' => (int)$m['id'], 'name' => (string)$m['name']];
            }
        }

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/setlist_show.php';
    }

    public function edit(): void {
        $this->auth->requireLogin();
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        $eventId = (int)($_GET['event_id'] ?? 0);
        if ($eventId === 0) {
            header('Location: /hinata/events.php');
            exit;
        }

        $eventModel = new EventModel();
        $event = $eventModel->find($eventId);
        if (!$event) {
            header('Location: /hinata/events.php');
            exit;
        }

        $setlistModel = new SetlistModel();
        $setlist = $setlistModel->getByEventId($eventId);

        $songModel = new SongModel();
        $songs = $songModel->getAllSongsWithRelease();
        $allSongs = array_map(fn($s) => [
            'id' => (int)$s['id'],
            'title' => (string)$s['title'],
            'release_id' => (int)$s['release_id'],
            'release_title' => (string)($s['release_title'] ?? ''),
            'release_type' => (string)($s['release_type'] ?? ''),
            'release_number' => $s['release_number'] !== null ? (int)$s['release_number'] : null,
            'release_date' => (string)($s['release_date'] ?? ''),
        ], $songs);

        $memberModel = new MemberModel();
        $members = $memberModel->getActiveMembersWithColors();
        $allMembers = array_map(fn($m) => [
            'id' => (int)$m['id'],
            'name' => (string)$m['name'],
            'generation' => (int)($m['generation'] ?? 0),
            'kana' => (string)($m['kana'] ?? ''),
        ], $members);

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/setlist_edit.php';
    }
}

