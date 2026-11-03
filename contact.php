<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container">
    <div class="section-header">
        <span class="section-subtitle">We Are Here For You</span>
        <h1 class="section-title">Contact TIMEAURA Concierge</h1>
    </div>

    <div class="catalog-container" style="grid-template-columns: 1fr 1fr; padding: 0;">
        <!-- Contact Form -->
        <div class="summary-card" style="background: var(--bg-card);">
            <h3 style="font-family: var(--font-heading); margin-bottom: 20px; color: var(--primary-gold);">
                Send Us A Message
            </h3>

            <div id="contactAlert" style="display:none; padding:12px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:0.9rem;"></div>

            <form id="contactForm" action="api/contact_submit.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Ananya Patel">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="ananya@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Indian Mobile Number (+91) *</label>
                    <div class="phone-input-group">
                        <span class="phone-prefix">+91</span>
                        <input type="tel" name="mobile" class="form-control indian-mobile-input" required placeholder="9876543210" maxlength="13">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Your Inquiry / Message *</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="Tell us how we can assist you with watch selection, custom orders, or warranty..."></textarea>
                </div>

                <button type="submit" class="btn btn-gold" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Submit Inquiry
                </button>
            </form>
        </div>

        <!-- Corporate Contact Details -->
        <div class="summary-card" style="background: var(--bg-surface);">
            <h3 style="font-family: var(--font-heading); margin-bottom: 25px; color: var(--text-main);">
                Store & Concierge Details
            </h3>

            <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--gold-glow); border: 1px solid var(--border-gold); color: var(--primary-gold); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div>
                    <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Headquarters Address</strong>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                        TIMEAURA Horology Tower, Level 14, BKC Financial Center, Bandra Kurla Complex, Mumbai, Maharashtra 400051, India
                    </span>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--gold-glow); border: 1px solid var(--border-gold); color: var(--primary-gold); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div>
                    <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Phone Hotline</strong>
                    <span style="color: var(--primary-gold); font-weight: 700; font-size: 1.05rem;">+91 98765 43210</span>
                    <span style="display: block; color: var(--text-muted); font-size: 0.82rem;">Toll-free Pan-India Concierge Support</span>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--gold-glow); border: 1px solid var(--border-gold); color: var(--primary-gold); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Email Support</strong>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">support@timeaura.in</span>
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--gold-glow); border: 1px solid var(--border-gold); color: var(--primary-gold); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Operating Hours</strong>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">Monday &ndash; Saturday: 10:00 AM &ndash; 7:00 PM IST</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const alertBox = document.getElementById('contactAlert');
    const formData = new FormData(this);

    fetch('api/contact_submit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alertBox.style.display = 'block';
        if (data.status === 'success') {
            alertBox.style.background = 'rgba(16, 185, 129, 0.15)';
            alertBox.style.color = '#34d399';
            alertBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            alertBox.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            this.reset();
        } else {
            alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
            alertBox.style.color = '#f87171';
            alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            alertBox.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + data.message;
        }
    })
    .catch(err => {
        alertBox.style.display = 'block';
        alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
        alertBox.style.color = '#f87171';
        alertBox.innerHTML = 'An unexpected network error occurred.';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
