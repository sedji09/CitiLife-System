<?php
/**
 * CaseStatusTransition.php
 * Enforces forward-only state machine transitions for X-ray cases and RadTech error correction workflow.
 */

class CaseStatusTransition
{
    /**
     * Strict 5-step workflow order for RadTech error correction.
     * Higher numbers mean later states. A status may only transition to equal or higher values.
     */
    private static $errorWorkflowOrder = [
        'Issue Reported'               => 1,
        'For RadTech Review'           => 2,
        'Pending RadTech Review'       => 2, // Backward-compatible alias
        'Correction in Progress'       => 3,
        'Correction Completed'         => 4,
        'Pending RadTech Verification' => 4, // Backward-compatible alias
        'Resolved'                     => 5,
    ];

    /**
     * General examination workflow order (for standard non-disputed cases).
     */
    private static $examWorkflowOrder = [
        'Pending'           => 1,
        'Pending Payment'   => 2,
        'Payment Verifying' => 2,
        'Payment Verified'  => 3,
        'Approved'          => 3,
        'X-ray Taken'       => 4,
        'Under Reading'     => 4,
        'Report Ready'      => 5,
        'Completed'         => 6,
        'Released'          => 6,
    ];

    /**
     * Determine if a transition from $from to $to is valid (forward-only).
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function canTransition(string $from, string $to): bool
    {
        $from = trim($from);
        $to = trim($to);

        // Same status is always permitted (idempotent)
        if ($from === $to) {
            return true;
        }

        // Within the error correction workflow (forward-only: 1 -> 2 -> 3 -> 4 -> 5):
        if (isset(self::$errorWorkflowOrder[$from]) && isset(self::$errorWorkflowOrder[$to])) {
            return self::$errorWorkflowOrder[$to] >= self::$errorWorkflowOrder[$from];
        }

        // Entry point: Moving into the error workflow from standard exam states (e.g., Released, Completed)
        if (isset(self::$errorWorkflowOrder[$to])) {
            return true;
        }

        // Standard exam flow transition (forward-only: 1 -> 2 -> 3 -> 4 -> 5 -> 6):
        if (isset(self::$examWorkflowOrder[$from]) && isset(self::$examWorkflowOrder[$to])) {
            return self::$examWorkflowOrder[$to] >= self::$examWorkflowOrder[$from];
        }

        // Fallback: allow transition if no strict restriction defined
        return true;
    }

    /**
     * Get numerical step (1-5) for error correction workflow.
     *
     * @param string $status
     * @return int
     */
    public static function getErrorStep(string $status): int
    {
        $status = trim($status);
        if ($status === 'Issue Reported') {
            return 2;
        }
        if (in_array($status, ['For RadTech Review', 'Pending RadTech Review', 'Correction in Progress'])) {
            return 3;
        }
        if (in_array($status, ['Correction Completed', 'Pending RadTech Verification', 'Resolved', 'Edited'])) {
            return 4;
        }
        return 2;
    }

    /**
     * Check if a given status belongs to the error correction workflow.
     *
     * @param string $status
     * @return bool
     */
    public static function isErrorWorkflow(string $status): bool
    {
        $status = trim($status);
        return array_key_exists($status, self::$errorWorkflowOrder);
    }

    /**
     * Get 4-step list for UI rendering (Edited removed).
     *
     * @return array
     */
    public static function getErrorStepsList(): array
    {
        return [
            1 => 'Issue Reported',
            2 => 'For RadTech Review',
            3 => 'Correction in Progress',
            4 => 'Correction Completed',
        ];
    }
}
