<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class PersonalAccessToken extends Model
{
    private static ?self $instance = null;

    /**
     * Get or create a singleton instance of PersonalAccessToken model.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a personal access token.
     * Hashes token with hash('sha256', $plainTextToken) and inserts record.
     *
     * @param int|string $userId ID of the user owning the token
     * @param string $tokenName Name/descriptor for the token (e.g. "mobile_app", "web_token")
     * @param string $plainTextToken Plain-text token to be hashed and stored
     * @param int|float|null $expiryDays Number of days until token expires, or null for no expiry
     * @return array|false Created token record on success, false on failure
     */
    public static function createToken(mixed $userId, string $tokenName, string $plainTextToken, mixed $expiryDays = null): array|false
    {
        $self = self::getInstance();
        $hashedToken = hash('sha256', $plainTextToken);

        $expiresAt = null;
        if ($expiryDays !== null && is_numeric($expiryDays)) {
            $days = (int)$expiryDays;
            $sign = $days >= 0 ? "+{$days}" : "{$days}";
            $expiresAt = date('Y-m-d H:i:s', strtotime("{$sign} days"));
        }

        $sql = "INSERT INTO personal_access_tokens (tokenable_id, name, token, expires_at, created_at)
                VALUES (:tokenable_id, :name, :token, :expires_at, NOW())";

        $stmt = $self->db->prepare($sql);
        $result = $stmt->execute([
            'tokenable_id' => $userId,
            'name'         => $tokenName,
            'token'        => $hashedToken,
            'expires_at'   => $expiresAt,
        ]);

        if ($result) {
            $insertId = (int)$self->getLastInsertId();
            return [
                'id'           => $insertId,
                'tokenable_id' => (int)$userId,
                'name'         => $tokenName,
                'token'        => $hashedToken,
                'expires_at'   => $expiresAt,
                'created_at'   => date('Y-m-d H:i:s'),
            ];
        }

        return false;
    }

    /**
     * Find a valid token by its plain-text representation.
     * Hashes incoming token, looks up matching record, checks if expires_at is still valid,
     * updates last_used_at, and returns the associated user record.
     *
     * @param string $plainTextToken Plain-text token sent by client
     * @return array|false The associated user record on success, or false if invalid/expired
     */
    public static function findToken(string $plainTextToken): array|false
    {
        if (empty($plainTextToken)) {
            return false;
        }

        $self = self::getInstance();
        $hashedToken = hash('sha256', $plainTextToken);

        $sql = "SELECT * FROM personal_access_tokens WHERE token = :token OR token = :plain LIMIT 1";
        $stmt = $self->db->prepare($sql);
        $stmt->execute([
            'token' => $hashedToken,
            'plain' => $plainTextToken,
        ]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            return false;
        }

        // Check if token has expired
        if (!empty($tokenRecord['expires_at'])) {
            $expiresAtTimestamp = strtotime($tokenRecord['expires_at']);
            if ($expiresAtTimestamp !== false && $expiresAtTimestamp <= time()) {
                return false;
            }
        }

        // Update last_used_at timestamp
        $updateStmt = $self->db->prepare("UPDATE personal_access_tokens SET last_used_at = NOW() WHERE id = :id");
        $updateStmt->execute(['id' => $tokenRecord['id']]);

        // Retrieve and return the associated user record
        $userStmt = $self->db->prepare("SELECT id, name, email, created_at, updated_at FROM users WHERE id = :id LIMIT 1");
        $userStmt->execute(['id' => $tokenRecord['tokenable_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        $user['id'] = (int)$user['id'];
        $user['token_id'] = (int)$tokenRecord['id'];
        $user['token_name'] = $tokenRecord['name'];

        return $user;
    }

    /**
     * Revoke / delete personal access token on logout.
     * Deletes or invalidates the token record.
     *
     * @param string $plainTextToken Plain-text token (or token hash) to revoke
     * @return bool True if record was deleted/revoked, false otherwise
     */
    public static function revokeToken(string $plainTextToken): bool
    {
        if (empty($plainTextToken)) {
            return false;
        }

        $self = self::getInstance();
        $hashedToken = hash('sha256', $plainTextToken);

        $stmt = $self->db->prepare("DELETE FROM personal_access_tokens WHERE token = :token OR token = :plain");
        $stmt->execute([
            'token' => $hashedToken,
            'plain' => $plainTextToken,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Revoke all personal access tokens for a specific user.
     *
     * @param int|string $userId
     * @return int Number of tokens revoked
     */
    public static function revokeAllForUser(mixed $userId): int
    {
        $self = self::getInstance();
        $stmt = $self->db->prepare("DELETE FROM personal_access_tokens WHERE tokenable_id = :user_id");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }

    /**
     * Delete expired tokens from database.
     *
     * @return int Number of expired tokens purged
     */
    public static function purgeExpired(): int
    {
        $self = self::getInstance();
        $stmt = $self->db->prepare("DELETE FROM personal_access_tokens WHERE expires_at IS NOT NULL AND expires_at <= NOW()");
        $stmt->execute();

        return $stmt->rowCount();
    }
}
