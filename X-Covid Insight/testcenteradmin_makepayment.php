<?php
session_start();
require 'db.php';

// Check if user is logged in as test center admin
if (!isset($_SESSION['email'])) {
    header("Location: loginPage.html");
    exit();
}

// Generate random due days between 5-30 days
$dueDays = rand(5, 30);
$dueDate = date('M j, Y', strtotime("+$dueDays days"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .payment-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<?php if (isset($_SESSION['flash'])): ?>
<div class="bg-green-100 border-l-4 border-green-600 text-green-800 p-4 rounded mb-4">
    <?= $_SESSION['flash']['message']; ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>



<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto payment-card rounded-2xl shadow-2xl p-8 relative">
            <!-- Back Button -->
            <a href="testcenteradmin_homepage.php" 
               class="absolute top-6 left-6 text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>

            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Payment Portal</h1>
                <div class="mt-4 text-lg text-gray-600">
                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">
                        Due in <?= $dueDays ?> days (<?= $dueDate ?>)
                    </span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Subscription Info -->
                <div class="bg-gray-50 p-6 rounded-xl">
                    <h3 class="text-lg font-semibold mb-4">Subscription Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Plan:</span>
                            <span class="font-medium capitalize"><?= $_SESSION['subscription_plan'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Billing Cycle:</span>
                            <span class="font-medium"><?= $_SESSION['billing_plan'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Testers:</span>
                            <span class="font-medium"><?= $_SESSION['testers'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Amount Card -->
                <div class="bg-blue-50 p-6 rounded-xl">
                    <h3 class="text-lg font-semibold mb-4">Payment Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span>Subtotal:</span>
                            <span class="font-medium" id="subtotal">-</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Discount:</span>
                            <span class="text-green-600 font-medium" id="discount">-</span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-3">
                            <span class="font-semibold">Total:</span>
                            <span class="text-xl font-bold text-blue-600" id="total">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form id="paymentForm" method="POST" class="space-y-6">
                <!-- Card Details -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
                        <div class="relative">
                            <input type="text" 
                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   name="card_number"
                                   placeholder="4242 4242 4242 4242"
                                   maxlength="19">
                            <div class="absolute right-3 top-3 flex space-x-2">
                                <img src="https://img.icons8.com/color/48/000000/visa.png" class="w-8 h-8"/>
                                <img src="https://img.icons8.com/color/48/000000/mastercard.png" class="w-8 h-8"/>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date</label>
                            <input type="text" 
                                   name="expiry"
                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="MM/YY">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CVC</label>
                            <input type="text" 
                                   name="cvc"
                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="123">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                        <input type="text" 
                               name="cardholder"
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="John Doe">
                    </div>
                </div>

                <input type="hidden" name="total" id="hiddenTotal">

                <!-- Payment Button -->
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-4 rounded-xl hover:bg-blue-700 transition-all
                               flex items-center justify-center space-x-2">
                    <i class="fas fa-lock"></i>
                    <span>Pay Now</span>
                </button>

                <!-- Security Info -->
                <p class="text-center text-sm text-gray-500">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Secure transaction with SSL encryption
                </p>
            </form>
        </div>
    </div>

    <script>
        // Pricing calculation
        const planPrices = {
            basic: 19.99,
            intermediate: 29.99,
            premium: 39.99
        };

        const billingCycles = {
            '1_month': 1,
            '6_months': 6,
            '1_year': 12
        };

        function calculateTotal() {
            const plan = "<?= $_SESSION['subscription_plan'] ?>";
            const billingCycle = "<?= $_SESSION['billing_plan'] ?>";
            const months = billingCycles[billingCycle] || 1;
            
            let subtotal = planPrices[plan] * months;
            let discount = 0;

            // Apply discounts
            if (months === 6) discount = subtotal * 0.1;
            if (months === 12) discount = subtotal * 0.2;

            const total = subtotal - discount;

            // Update display
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = `-$${discount.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
            document.getElementById('hiddenTotal').value = total.toFixed(2);
        }

        // Initial calculation
        calculateTotal();


        // Handle form submission with popup
        document.getElementById("paymentForm").addEventListener("submit", function(e) {
            e.preventDefault(); // Prevent actual form submission

            // Validate credit card input
            const cardNumber = document.querySelector('input[placeholder="4242 4242 4242 4242"]').value.trim();
            const expiry = document.querySelector('input[placeholder="MM/YY"]').value.trim();
            const cvc = document.querySelector('input[placeholder="123"]').value.trim();
            const name = document.querySelector('input[placeholder="John Doe"]').value.trim();

            // Card Number Validation (spaces are allowed, but must be 16 digits)
            const sanitizedCard = cardNumber.replace(/\s+/g, '');
            if (!/^\d{16}$/.test(sanitizedCard)) {
                alert("Invalid card number. Must be 16 digits.");
                return;
            }

            // CVC Expiry Validation (MM/YY format and future date)
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                alert("Invalid expiration date. Use MM/YY format.");
                return;
            }

            const [mm, yy] = expiry.split('/').map(Number);
            const current = new Date();
            const expiryDate = new Date(2000 + yy, mm - 1); // 2000+yy to normalize year

            if (expiryDate < new Date(current.getFullYear(), current.getMonth())) {
                alert("Card has expired.");
                return;
            }

            // CVC Validation
            if (!/^\d{3}$/.test(cvc)) {
                alert("Invalid CVC. Must be 3 digits.");
                return;
            }

            // Name Validation
            if (name.length === 0) {
                alert("Cardholder name is required.");
                return;
            }

            // Show success popup
            alert("💳 Payment successful!");

            // Redirect back to homepage after success
            window.location.href = "testcenteradmin_homepage.php";
        });


    </script>
</body>
</html>