<?php
// This file should be included in pages to display user entitlements

require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/entitlements-check.php";

if (!isAuthenticated()) {
    return;
}

$userId = getUserId();
if ($userId === null) {
    return;
}

$entitlements = getEntitlementsSummary($userId);
$creditsBalance = $entitlements['credits_balance'];
$hasUnlimited = $entitlements['has_unlimited'];
$unlimitedUntilDate = $entitlements['unlimited_until'];
$planName = $entitlements['plan_name'];
?>

<div class="entitlements-bar">
    <?php if ($hasUnlimited) : ?>
        <div class="entitlement-item subscription">
            <span class="entitlement-label">Subscription:</span>
            <span class="entitlement-value">
                <?php echo htmlspecialchars($planName ?? "Unlimited"); ?>
                <?php if ($unlimitedUntilDate) : ?>
                    <?php
                    $endDate = new DateTime($unlimitedUntilDate);
                    $now = new DateTime();
                    $daysLeft = $now->diff($endDate)->days;
                    if ($endDate > $now && $daysLeft > 0) {
                        echo " (expires in " . $daysLeft . " day" . ($daysLeft > 1 ? "s" : "") . ")";
                    } else {
                        echo " (expires today)";
                    }
                    ?>
                <?php endif; ?>
            </span>
        </div>
    <?php else : ?>
        <div class="entitlement-item credits">
            <span class="entitlement-label">Credits:</span>
            <span class="entitlement-value"><?php echo $creditsBalance; ?></span>
        </div>
    <?php endif; ?>
</div>
