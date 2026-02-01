<?php
require_once __DIR__ . "/config/auth.php";

if (!isAuthenticated()) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . "/config/db.php";

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT code, name, price_cents, currency, plan_type FROM plans WHERE is_active = 1 ORDER BY price_cents ASC");
    $stmt->execute();
    $plans = $stmt->fetchAll();
} catch (Exception $e) {
    $plans = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans</title>
    <link rel="stylesheet" href="css/home.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <a class="back-btn" href="index.php">← Back</a>
        </div>
        <div class="topbar-center">
            <h1 class="brand">IELTSEVALAI</h1>
        </div>
        <div class="topbar-right"></div>
    </header>

    <?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

    <main class="container">
        <div class="page-title">Choose a Plan</div>
        
        <div class="card-grid">
            <?php foreach ($plans as $plan): ?>
                <?php
                $price = (float)$plan["price_cents"] / 100;
                $currency = strtoupper($plan["currency"] ?? "EUR");
                $planType = $plan["plan_type"];
                ?>
                <form action="checkout.php" method="POST" style="display: contents;">
                    <button type="submit" name="plan" value="<?php echo htmlspecialchars($plan["code"]); ?>" class="mode-card" style="cursor: pointer; border: none; text-align: left;">
                        <h2><?php echo htmlspecialchars($plan["name"]); ?></h2>
                        <p>
                            <?php if ($planType === "credits"): ?>
                                Get <?php echo htmlspecialchars($plan["name"]); ?> for unlimited practice sessions.
                            <?php else: ?>
                                Unlimited access for the subscription period.
                            <?php endif; ?>
                        </p>
                        <div style="margin-top: 16px; font-size: 24px; font-weight: 700; color: #c40000;">
                            <?php echo number_format($price, 2); ?> <?php echo htmlspecialchars($currency); ?>
                        </div>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>