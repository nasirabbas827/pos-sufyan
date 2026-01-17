<!DOCTYPE html>
<html lang="en">
<head>
    <title>Karobar POS • Smart Sales & Inventory</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: white;
            text-align: center;
            padding: 5rem 1rem;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .hero h1 {
            font-size: clamp(2rem, 6vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .hero p {
            font-size: clamp(1rem, 3vw, 1.25rem);
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 2rem;
        }

        /* Buttons */
        .btn-custom {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0.5rem;
            display: inline-flex;
            align-items: center;
        }

        .btn-login {
            background-color: #fff;
            color: #1a2a6c;
            border: none;
        }

        .btn-login:hover {
            background-color: #fdbb2d;
            color: #000;
            transform: scale(1.05);
        }

        /* Feature section */
        .section-title {
            text-align: center;
            margin-top: 4rem;
            font-weight: 700;
            color: #333;
        }

        .feature-cards {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 3rem 1rem;
        }

        .feature-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 350px;
            padding: 2rem;
            transition: all 0.3s;
            border-bottom: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom: 4px solid #b21f1f;
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
            color: #b21f1f;
        }

        .feature-card h5 {
            font-weight: 700;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .feature-card ul {
            list-style: none;
            padding: 0;
        }

        .feature-card ul li {
            margin-bottom: 0.75rem;
            color: #555;
            font-size: 0.95rem;
            display: flex;
            align-items: flex-start;
        }

        .feature-card ul li i {
            color: #28a745;
            margin-right: 10px;
            margin-top: 4px;
        }

        /* Footer */
        footer {
            background: #222;
            color: #fff;
            padding: 3rem 1rem;
            margin-top: 5rem;
            text-align: center;
        }

        .social-icons a {
            color: #fff;
            margin: 0 1rem;
            font-size: 1.5rem;
            opacity: 0.7;
            transition: 0.3s;
        }

        .social-icons a:hover {
            opacity: 1;
            color: #fdbb2d;
        }

        @media (max-width: 768px) {
            .hero { padding: 3rem 1rem; }
            .feature-card { max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="hero">
        <h1>Karobar POS</h1>
        <p>Streamline your sales, automate inventory tracking, and manage customers with our all-in-one Point of Sale solution.</p>
        <div>
            <a class="btn btn-custom btn-login shadow" href="login.php">
                <i class="fas fa-sign-in-alt mr-2"></i> Access Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <h2 class="section-title">Core Capabilities</h2>
        <div class="feature-cards">
            
            <div class="feature-card">
                <div class="icon-circle shadow-sm"><i class="fas fa-file-invoice-dollar"></i></div>
                <h5>Smart Transactions</h5>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Real-time Invoice Generation</li>
                    <li><i class="fas fa-check-circle"></i> Dynamic Discount Application</li>
                    <li><i class="fas fa-check-circle"></i> Fast Product Search & Filter</li>
                    <li><i class="fas fa-check-circle"></i> Multi-item Cart Processing</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="icon-circle shadow-sm"><i class="fas fa-boxes-stacked"></i></div>
                <h5>Inventory Control</h5>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Automatic Stock Updates</li>
                    <li><i class="fas fa-check-circle"></i> Low-Stock Instant Alerts</li>
                    <li><i class="fas fa-check-circle"></i> Category-wise Organization</li>
                    <li><i class="fas fa-check-circle"></i> Product History Tracking</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="icon-circle shadow-sm"><i class="fas fa-users-gear"></i></div>
                <h5>Role-Based Access</h5>
                <ul>
                    <li><i class="fas fa-check-circle"></i> <b>Admin:</b> Full System Control</li>
                    <li><i class="fas fa-check-circle"></i> <b>Manager:</b> Inventory & Reports</li>
                    <li><i class="fas fa-check-circle"></i> <b>Cashier:</b> Sales & Receipts</li>
                    <li><i class="fas fa-check-circle"></i> Secure Authentication</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="icon-circle shadow-sm"><i class="fas fa-user-tag"></i></div>
                <h5>Customer CRM</h5>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Profile Name & Phone Tracking</li>
                    <li><i class="fas fa-check-circle"></i> "Random Customer" Quick Entry</li>
                    <li><i class="fas fa-check-circle"></i> Purchase History Logs</li>
                    <li><i class="fas fa-check-circle"></i> Loyalty/Discount Readiness</li>
                </ul>
            </div>

        </div>
    </div>

    <footer>
        <div class="social-icons mb-4">
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p>© 2026 Karobar POS System. Smart Solutions for Modern Commerce.</p>
        <small class="text-muted">Developed for efficient Sales & Inventory Management</small>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>