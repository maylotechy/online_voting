<?php
function showNotFoundPage($options = []) {
    // Default options
    $defaults = [
        'title' => 'Oops! Page Not Found',
        'message' => 'The content you\'re looking for doesn\'t exist or might have been moved.',
        'primary_action' => [
            'text' => 'Go Back',
            'url' => 'javascript:history.back()',
            'icon' => 'arrow-left'
        ],
        'secondary_action' => [
            'text' => 'Return Home',
            'url' => 'voting_page.php',
            'icon' => 'home'
        ]
    ];

    $options = array_merge($defaults, $options);

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($options['title']) ?></title>
        <!-- Your existing CSS imports -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #4361ee;
                --secondary: #3f37c9;
                --danger: #e63946;
                --light: #f8f9fa;
                --dark: #212529;
            }

            .not-found-container {
                font-family: 'Poppins', sans-serif;
                background-color: #f5f7fa;
                height: 100vh;
                display: flex;
                flex-direction: column;
                margin: 0;
                overflow-x: hidden;
            }

            .not-found-wrapper {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                position: relative;
            }

            .not-found-card {
                max-width: 800px;
                width: 100%;
                border: none;
                border-radius: 24px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
                overflow: hidden;
                text-align: center;
                background: white;
                padding: 3rem 2rem;
                position: relative;
                z-index: 2;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.85);
            }

            .error-title {
                font-weight: 700;
                font-size: 2.5rem;
                margin: 1rem 0;
                color: var(--dark);
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .error-subtitle {
                color: #6c757d;
                font-size: 1.2rem;
                margin-bottom: 2.5rem;
                line-height: 1.6;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .error-actions {
                display: flex;
                gap: 1.5rem;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 2rem;
            }

            .btn-notfound-primary {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                border: none;
                padding: 0.9rem 2rem;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s;
                box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
                display: inline-flex;
                align-items: center;
            }

            .btn-notfound-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
                color: white;
            }

            .btn-notfound-secondary {
                background-color: white;
                color: var(--primary);
                border: 2px solid var(--primary);
                padding: 0.9rem 2rem;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
            }

            .btn-notfound-secondary:hover {
                background-color: rgba(67, 97, 238, 0.05);
                transform: translateY(-3px);
                color: var(--primary);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .error-title {
                    font-size: 2rem;
                }

                .error-subtitle {
                    font-size: 1rem;
                }

                .error-actions {
                    flex-direction: column;
                    gap: 1rem;
                }

                .btn-notfound-primary,
                .btn-notfound-secondary {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    </head>
    <body class="not-found-container">
    <div class="not-found-wrapper">
        <div class="not-found-card">
            <!-- Removed all visual elements, keeping only text -->
            <h1 class="error-title"><?= htmlspecialchars($options['title']) ?></h1>
            <p class="error-subtitle">
                <?= htmlspecialchars($options['message']) ?>
            </p>
            <div class="error-actions">
                <a href="<?= htmlspecialchars($options['primary_action']['url']) ?>" class="btn btn-notfound-primary">
                    <i class="fas fa-<?= htmlspecialchars($options['primary_action']['icon']) ?> me-2"></i>
                    <?= htmlspecialchars($options['primary_action']['text']) ?>
                </a>
                <a href="<?= htmlspecialchars($options['secondary_action']['url']) ?>" class="btn btn-notfound-secondary">
                    <i class="fas fa-<?= htmlspecialchars($options['secondary_action']['icon']) ?> me-2"></i>
                    <?= htmlspecialchars($options['secondary_action']['text']) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Your existing JS imports -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </body>
    </html>
    <?php
    $content = ob_get_clean();
    echo $content;
    exit();
}
?>