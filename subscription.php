<?php
require_once 'includes/header.php';
require_once 'config/payment.php';

$plans = include 'config/payment.php';
$plans = $plans['plans'];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 text-center mb-5">
            <h1 class="display-4">خطط الاشتراك</h1>
            <p class="lead">اختر الخطة المناسبة لاحتياجاتك</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- الخطة المجانية -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 shadow">
                <div class="card-header bg-light text-center py-3">
                    <h4 class="my-0 font-weight-bold"><?php echo $plans['free']['name_ar']; ?></h4>
                </div>
                <div class="card-body">
                    <h1 class="card-title text-center">مجاناً</h1>
                    <ul class="list-unstyled mt-3 mb-4 text-right">
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['free']['monthly_video_quota']; ?> فيديو شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['free']['monthly_image_quota']; ?> صورة شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> دقة <?php echo $plans['free']['max_resolution']; ?></li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['free']['max_devices']; ?> جهاز</li>
                        <li><i class="fas fa-exclamation-circle text-warning ml-2"></i> مع إعلانات</li>
                    </ul>
                    <div class="text-center mt-auto">
                        <button class="btn btn-outline-primary btn-lg btn-block" disabled>الخطة الحالية</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- الاشتراك الأساسي الشهري -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 shadow">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="my-0 font-weight-bold"><?php echo $plans['basic_monthly']['name_ar']; ?></h4>
                </div>
                <div class="card-body">
                    <h1 class="card-title text-center">$<?php echo $plans['basic_monthly']['price']; ?><small class="text-muted">/شهر</small></h1>
                    <ul class="list-unstyled mt-3 mb-4 text-right">
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_monthly']['monthly_video_quota']; ?> فيديو شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_monthly']['monthly_image_quota']; ?> صورة شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> دقة <?php echo $plans['basic_monthly']['max_resolution']; ?></li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_monthly']['max_devices']; ?> أجهزة</li>
                        <li><i class="fas fa-check text-success ml-2"></i> بدون إعلانات</li>
                    </ul>
                    <div class="text-center mt-auto">
                        <div id="paypal-button-container-<?php echo $plans['basic_monthly']['paypal_button_id']; ?>"></div>
                        <a href="<?php echo $plans['basic_monthly']['stripe_link']; ?>" class="btn btn-success btn-lg btn-block mt-2">
                            <i class="fab fa-stripe-s mr-2"></i>الدفع بواسطة Stripe
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- الاشتراك الأساسي السنوي -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 shadow border-primary">
                <div class="card-header bg-primary text-white text-center py-3">
                    <span class="badge badge-warning position-absolute" style="top: -10px; right: 10px;">الأفضل قيمة</span>
                    <h4 class="my-0 font-weight-bold"><?php echo $plans['basic_annual']['name_ar']; ?></h4>
                </div>
                <div class="card-body">
                    <h1 class="card-title text-center">$<?php echo $plans['basic_annual']['price']; ?><small class="text-muted">/سنة</small></h1>
                    <ul class="list-unstyled mt-3 mb-4 text-right">
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_annual']['monthly_video_quota']; ?> فيديو شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_annual']['monthly_image_quota']; ?> صورة شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> دقة <?php echo $plans['basic_annual']['max_resolution']; ?></li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['basic_annual']['max_devices']; ?> أجهزة</li>
                        <li><i class="fas fa-check text-success ml-2"></i> بدون إعلانات</li>
                        <li><i class="fas fa-star text-warning ml-2"></i> توفير 17% مقارنة بالاشتراك الشهري</li>
                    </ul>
                    <div class="text-center mt-auto">
                        <div id="paypal-button-container-<?php echo $plans['basic_annual']['paypal_button_id']; ?>"></div>
                        <a href="<?php echo $plans['basic_annual']['stripe_link']; ?>" class="btn btn-success btn-lg btn-block mt-2">
                            <i class="fab fa-stripe-s mr-2"></i>الدفع بواسطة Stripe
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- الاشتراك الاحترافي -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 shadow border-success">
                <div class="card-header bg-success text-white text-center py-3">
                    <span class="badge badge-danger position-absolute" style="top: -10px; right: 10px;">احترافي</span>
                    <h4 class="my-0 font-weight-bold"><?php echo $plans['pro']['name_ar']; ?></h4>
                </div>
                <div class="card-body">
                    <h1 class="card-title text-center">$<?php echo $plans['pro']['price']; ?><small class="text-muted">/سنة</small></h1>
                    <ul class="list-unstyled mt-3 mb-4 text-right">
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['pro']['monthly_video_quota']; ?> فيديو شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['pro']['monthly_image_quota']; ?> صورة شهرياً</li>
                        <li><i class="fas fa-check text-success ml-2"></i> دقة <?php echo $plans['pro']['max_resolution']; ?></li>
                        <li><i class="fas fa-check text-success ml-2"></i> <?php echo $plans['pro']['max_devices']; ?> أجهزة</li>
                        <li><i class="fas fa-check text-success ml-2"></i> بدون إعلانات</li>
                        <li><i class="fas fa-star text-warning ml-2"></i> أعلى جودة متاحة</li>
                    </ul>
                    <div class="text-center mt-auto">
                        <div id="paypal-button-container-<?php echo $plans['pro']['paypal_button_id']; ?>"></div>
                        <a href="<?php echo $plans['pro']['stripe_link']; ?>" class="btn btn-success btn-lg btn-block mt-2">
                            <i class="fab fa-stripe-s mr-2"></i>الدفع بواسطة Stripe
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PayPal JavaScript SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo $plans['paypal']['client_id']; ?>&currency=USD"></script>

<script>
    // تهيئة أزرار PayPal
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?php echo $plans['basic_monthly']['price']; ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                window.location.href = 'payment_success.php?plan=basic_monthly&transaction_id=' + details.id;
            });
        }
    }).render('#paypal-button-container-<?php echo $plans['basic_monthly']['paypal_button_id']; ?>');

    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?php echo $plans['basic_annual']['price']; ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                window.location.href = 'payment_success.php?plan=basic_annual&transaction_id=' + details.id;
            });
        }
    }).render('#paypal-button-container-<?php echo $plans['basic_annual']['paypal_button_id']; ?>');
</script>

<?php require_once 'includes/footer.php'; ?>