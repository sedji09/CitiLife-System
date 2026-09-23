<?php
class NotificationModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function add($title, $message, $link = null, $userId = null, $role = null, $branchId = null) {
        if ($link !== null) {
            $link = html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($link, '//') === 0 && strpos($link, '://') === false) {
                $link = '/' . ltrim($link, '/');
            }

            // Convert legacy index.php?role=...&page=... into clean router path
            if (strpos($link, 'index.php?') !== false) {
                $parsed = parse_url($link);
                $query = $parsed['query'] ?? '';
                parse_str($query, $params);
                if (!empty($params['page'])) {
                    $targetPage = $params['page'];
                    unset($params['page']);
                    unset($params['role']);
                    $queryString = !empty($params) ? '?' . http_build_query($params) : '';
                    $link = function_exists('url') ? url($targetPage . $queryString) : ('/' . $targetPage . $queryString);
                }
            }

            // RadTech Online Registration patient request notifications must route to patient-approval
            if ($role === 'radtech' && (stripos($title, 'Patient Request') !== false || stripos($message, 'awaits approval') !== false)) {
                if (strpos($link, 'patient-approval') === false) {
                    $parsed = parse_url($link);
                    $query = !empty($parsed['query']) ? '?' . $parsed['query'] : '';
                    $link = function_exists('url') ? url('patient-approval' . $query) : ('/patient-approval' . $query);
                }
            }

            // Queue notifications pointing to patient-lists must have tab=queue
            if ((stripos($title, 'Queue') !== false || stripos($title, 'Report Ready') !== false || stripos($title, 'Revised Report') !== false) 
                && strpos($link, 'patient-lists') !== false && strpos($link, 'tab=') === false) {
                $link = str_replace('patient-lists?', 'patient-lists?tab=queue&', $link);
                if (strpos($link, 'tab=queue') === false) {
                    $link .= (strpos($link, '?') !== false ? '&' : '?') . 'tab=queue';
                }
            }

            // Correction / dispute notifications pointing to patient-lists must have tab=disputes
            if ((stripos($title, 'Correction') !== false || stripos($title, 'Dispute') !== false || stripos($title, 'Amended') !== false) 
                && strpos($link, 'patient-lists') !== false && strpos($link, 'tab=') === false) {
                $link = str_replace('patient-lists?', 'patient-lists?tab=disputes&', $link);
                if (strpos($link, 'tab=disputes') === false) {
                    $link .= (strpos($link, '?') !== false ? '&' : '?') . 'tab=disputes';
                }
            }
        }
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, role, branch_id, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $role, $branchId, $title, $message, $link]);
    }
}
