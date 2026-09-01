<?php
/**
 * Citilife System - Modern GitHub-Style Email Template Generator
 * 
 * Provides clean, responsive, and cross-client compatible HTML email layouts
 * following the minimalist GitHub aesthetic.
 */

if (!function_exists('renderGitHubStyleEmail')) {
    /**
     * Core renderer for GitHub-styled email
     *
     * @param array $params [
     *   'headerTitle'   => string (e.g. "Please verify your identity, sedji09"),
     *   'cardContent'   => string (HTML content inside the white card),
     *   'footerNotice'  => string (Contextual notice below card),
     *   'brandLogo'     => string (optional custom logo SVG or text),
     * ]
     * @return string
     */
    function renderGitHubStyleEmail($params = [])
    {
        $headerTitle = $params['headerTitle'] ?? '';
        $cardContent = $params['cardContent'] ?? '';
        $footerNotice = $params['footerNotice'] ?? 'You received this email because of an activity related to your Citilife account.';
        $currentYear = date('Y');

        $appUrl = getenv('APP_URL') ?: ($_SERVER['APP_URL'] ?? 'https://citilife-system-production.up.railway.app');
        $logoUrl = rtrim($appUrl, '/') . '/public/assets/img/logo/citilife-logo.png';

        $logoHtml = '<div style="display: inline-block; margin-bottom: 16px;">
            <img src="' . htmlspecialchars($logoUrl) . '" alt="Citilife" width="56" height="56" style="display: block; width: 56px; height: 56px; max-width: 56px; border-radius: 50%; object-fit: contain; margin: 0 auto; border: 0;" />
        </div>';

        return '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>' . htmlspecialchars(strip_tags($headerTitle)) . '</title>
    <!--[if mso]>
    <style type="text/css">
        table, td, div, p, a, h1, h2, h3, span { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f6f8fa; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #1f2328;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f6f8fa; width: 100%; min-height: 100vh; padding: 32px 16px;">
        <tr>
            <td align="center" valign="top">
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 520px; width: 100%; margin: 0 auto;">
                    <!-- Brand Icon & Header Title -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            ' . $logoHtml . '
                            ' . (!empty($headerTitle) ? '<h1 style="margin: 0; font-size: 24px; font-weight: 600; line-height: 1.3; color: #1f2328; letter-spacing: -0.3px; padding: 0 8px;">' . $headerTitle . '</h1>' : '') . '
                        </td>
                    </tr>

                    <!-- Main White Container Card -->
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #d0d7de; border-radius: 8px; padding: 32px 28px; box-shadow: 0 1px 3px rgba(31, 35, 40, 0.04);">
                            ' . $cardContent . '
                        </td>
                    </tr>

                    <!-- Outer Footer Details -->
                    <tr>
                        <td align="center" style="padding: 24px 16px 12px 16px; font-size: 12px; line-height: 18px; color: #656d76; text-align: center;">
                            <p style="margin: 0 0 8px 0;">' . $footerNotice . '</p>
                            <p style="margin: 0 0 8px 0; font-weight: 500;">Citilife Medical &amp; Diagnostic Center</p>
                            <p style="margin: 0; color: #8c959f;">&copy; ' . $currentYear . ' Citilife Diagnostic Center. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}

if (!function_exists('renderOtpEmail')) {
    /**
     * Renders a GitHub-styled OTP / Verification Code email
     *
     * @param string $recipientName First name or display name of the recipient
     * @param string $otpCode 6-digit or n-digit OTP code
     * @param string $purpose Short purpose text (e.g. "authentication", "login", "password reset", "email change")
     * @param int $expiryMinutes Validity duration in minutes
     * @param string|null $customHeading Optional custom header title
     * @param string|null $footerReason Optional contextual footer explanation
     * @return string Complete HTML email
     */
    function renderOtpEmail($recipientName, $otpCode, $purpose = 'login', $expiryMinutes = 15, $customHeading = null, $footerReason = null)
    {
        $safeName = htmlspecialchars($recipientName ?: 'there');
        $heading = $customHeading ?: "Please verify your identity, <strong>{$safeName}</strong>";

        if (!$footerReason) {
            $footerReason = "You're receiving this email because a verification code was requested for your Citilife account. If this wasn't you, please secure your account or ignore this message.";
        }

        $purposeLabel = "Citilife " . htmlspecialchars($purpose);

        $cardContent = '
            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 22px; color: #1f2328;">
                Here is your ' . $purposeLabel . ' code:
            </p>
            
            <div style="text-align: center; margin: 24px 0 28px 0;">
                <div style="display: inline-block; padding: 12px 28px; background-color: #f6f8fa; border: 1px solid #d0d7de; border-radius: 6px; font-family: ui-monospace, SFMono-Regular, \'SF Mono\', Menlo, Consolas, \'Liberation Mono\', monospace; font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #1f2328;">
                    ' . htmlspecialchars($otpCode) . '
                </div>
            </div>

            <p style="margin: 0 0 12px 0; font-size: 14px; line-height: 20px; color: #1f2328;">
                This code is valid for <strong>' . (int) $expiryMinutes . ' minutes</strong> and can only be used once.
            </p>

            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 20px; color: #1f2328;">
                <strong>Please don\'t share this code with anyone:</strong> we\'ll never ask for it on the phone or via email.
            </p>

            <div style="border-top: 1px solid #d0d7de; padding-top: 18px; margin-top: 24px;">
                <p style="margin: 0; font-size: 14px; line-height: 20px; color: #1f2328; font-weight: 500;">
                    Thank you for choosing <strong>Citilife</strong>
                </p>
            </div>
        ';

        return renderGitHubStyleEmail([
            'headerTitle' => $heading,
            'cardContent' => $cardContent,
            'footerNotice' => $footerReason
        ]);
    }
}

if (!function_exists('renderActionEmail')) {
    /**
     * Renders a GitHub-styled CTA action button email (e.g. Email verification, Password reset)
     *
     * @param string $recipientName Recipient's name
     * @param string $heading Main header title
     * @param string $introText Primary instruction / intro text
     * @param string $buttonText Text for the CTA button
     * @param string $buttonUrl URL destination for the button
     * @param string|null $expiryNote Expiration note if any
     * @param string|null $securityNote Security note if any
     * @param string|null $footerReason Contextual reason in footer
     * @param string $buttonColor Button background color (default '#dc2626' red)
     * @return string Complete HTML email
     */
    function renderActionEmail($recipientName, $heading, $introText, $buttonText, $buttonUrl, $expiryNote = null, $securityNote = null, $footerReason = null, $buttonColor = '#dc2626')
    {
        $safeName = htmlspecialchars($recipientName ?: 'there');
        if (!$footerReason) {
            $footerReason = "You're receiving this email because of a request associated with your Citilife account.";
        }

        $cardContent = '
            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 22px; color: #1f2328;">
                Hi <strong>' . $safeName . '</strong>,
            </p>
            <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 22px; color: #1f2328;">
                ' . $introText . '
            </p>
            
            <div style="text-align: center; margin: 28px 0 28px 0;">
                <a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" style="display: inline-block; padding: 12px 24px; background-color: ' . $buttonColor . '; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; letter-spacing: -0.1px; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                    ' . htmlspecialchars($buttonText) . '
                </a>
            </div>
        ';

        if ($expiryNote) {
            $cardContent .= '
                <p style="margin: 0 0 12px 0; font-size: 14px; line-height: 20px; color: #1f2328;">
                    ' . $expiryNote . '
                </p>
            ';
        }

        if ($securityNote) {
            $cardContent .= '
                <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 20px; color: #1f2328;">
                    ' . $securityNote . '
                </p>
            ';
        }

        // Direct link fallback
        $cardContent .= '
            <div style="background-color: #f6f8fa; border: 1px solid #d0d7de; border-radius: 6px; padding: 12px 14px; margin-top: 20px; word-break: break-all;">
                <p style="margin: 0; font-size: 12px; line-height: 18px; color: #656d76;">
                    If the button above does not work, copy and paste this URL into your browser:<br>
                    <a href="' . htmlspecialchars($buttonUrl) . '" style="color: #0969da; text-decoration: underline;">' . htmlspecialchars($buttonUrl) . '</a>
                </p>
            </div>

            <div style="border-top: 1px solid #d0d7de; padding-top: 18px; margin-top: 24px;">
                <p style="margin: 0; font-size: 14px; line-height: 20px; color: #1f2328; font-weight: 500;">
                    Thank you for choosing <strong>Citilife</strong>
                </p>
            </div>
        ';

        return renderGitHubStyleEmail([
            'headerTitle' => $heading,
            'cardContent' => $cardContent,
            'footerNotice' => $footerReason
        ]);
    }
}

if (!function_exists('renderNotificationEmail')) {
    /**
     * Renders a GitHub-styled notification email (e.g. Case Status, Dispute Resolution, Staff Account)
     *
     * @param string $recipientName Recipient's name
     * @param string $heading Main header title
     * @param string $introText Primary message text
     * @param array $details Key-value pairs of details to display in a clean table (optional)
     * @param string|null $buttonText Button text (optional)
     * @param string|null $buttonUrl Button URL (optional)
     * @param string|null $footerReason Footer notice
     * @param string $buttonColor Button color
     * @return string Complete HTML email
     */
    function renderNotificationEmail($recipientName, $heading, $introText, $details = [], $buttonText = null, $buttonUrl = null, $footerReason = null, $buttonColor = '#dc2626')
    {
        $safeName = htmlspecialchars($recipientName ?: 'there');
        if (!$footerReason) {
            $footerReason = "You're receiving this notification because of an update in your Citilife account records.";
        }

        $cardContent = '
            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 22px; color: #1f2328;">
                Hello <strong>' . $safeName . '</strong>,
            </p>
            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 22px; color: #1f2328;">
                ' . $introText . '
            </p>
        ';

        if (!empty($details)) {
            $cardContent .= '<div style="background-color: #f6f8fa; border: 1px solid #d0d7de; border-radius: 6px; padding: 12px 16px; margin: 18px 0;">';
            $isFirst = true;
            foreach ($details as $label => $val) {
                $marginTop = $isFirst ? '0' : '6px';
                $isFirst = false;
                $cardContent .= '<p style="margin: ' . $marginTop . ' 0 0 0; font-size: 13px; line-height: 20px; color: #1f2328; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">';
                $cardContent .= '<span style="font-weight: 600; color: #656d76; white-space: nowrap;">' . htmlspecialchars($label) . ':</span> ';
                $cardContent .= '<span style="font-weight: 600; color: #1f2328; white-space: nowrap;">' . $val . '</span>';
                $cardContent .= '</p>';
            }
            $cardContent .= '</div>';
        }

        if ($buttonText && $buttonUrl) {
            $cardContent .= '
                <div style="text-align: center; margin: 26px 0 20px 0;">
                    <a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" style="display: inline-block; padding: 12px 24px; background-color: ' . $buttonColor . '; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; letter-spacing: -0.1px; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                        ' . htmlspecialchars($buttonText) . '
                    </a>
                </div>
            ';
        }

        $cardContent .= '
            <div style="border-top: 1px solid #d0d7de; padding-top: 18px; margin-top: 24px;">
                <p style="margin: 0; font-size: 14px; line-height: 20px; color: #1f2328; font-weight: 500;">
                    Thank you for choosing <strong>Citilife</strong>
                </p>
            </div>
        ';

        return renderGitHubStyleEmail([
            'headerTitle' => $heading,
            'cardContent' => $cardContent,
            'footerNotice' => $footerReason
        ]);
    }
}
