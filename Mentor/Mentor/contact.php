<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Contact - Pathways</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .contact-info-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      height: 100%;
      transition: transform 0.3s;
    }
    
    .contact-info-card:hover {
      transform: translateY(-5px);
    }
    
    .info-item {
      display: flex;
      align-items: start;
      margin-bottom: 30px;
    }
    
    .info-item:last-child {
      margin-bottom: 0;
    }
    
    .info-item i {
      font-size: 32px;
      color: #5fcf80;
      margin-right: 20px;
      flex-shrink: 0;
    }
    
    .info-item h3 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }
    
    .info-item p {
      margin: 0;
      color: #666;
      line-height: 1.6;
    }
    
    .contact-form-card {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #5fcf80;
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }
    
    .opportunity-fields {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 15px;
      display: none;
    }
    
    .opportunity-fields.show {
      display: block;
    }
    
    .opportunity-fields h4 {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }
    
    .btn-submit {
      width: 100%;
      padding: 15px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-submit:hover {
      background: #4ab86a;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .alert-success {
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
    }
    
    .alert-error {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }
    
    .faq-section {
      background: #f8f9fa;
      padding: 60px 0;
      margin-top: 60px;
    }
    
    .faq-item {
      background: white;
      padding: 25px;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .faq-item h4 {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
    }
    
    .faq-item h4 i {
      color: #5fcf80;
      margin-right: 10px;
    }
    
    .faq-item p {
      color: #666;
      margin: 0;
      line-height: 1.6;
    }
  </style>
</head>

<body class="contact-page">

<?php include('components/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Contact Us</h1>
              <p class="mb-0">Have questions or want to suggest a new opportunity? We'd love to hear from you! Reach out to our team and we'll get back to you as soon as possible.</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Contact</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="mb-5" data-aos="fade-up" data-aos-delay="100">
        <iframe style="border:0; width: 100%; height: 350px; border-radius: 10px;" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2823.0844397878577!2d-93.07645492345795!3d44.94706957107126!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x87f7d5846aa5c5a1%3A0x4e9c9d7a7c6e5d9f!2s700%20E%207th%20St%2C%20St%20Paul%2C%20MN%2055106!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div><!-- End Google Maps -->

      <div class="container" data-aos="fade-up" data-aos-delay="200">

        <div class="row gy-4">

          <div class="col-lg-4">
            <div class="contact-info-card">
              <div class="info-item">
                <i class="bi bi-geo-alt"></i>
                <div>
                  <h3>Our Location</h3>
                  <p>700 E 7th St<br>St Paul, MN 55106</p>
                </div>
              </div>

              <div class="info-item">
                <i class="bi bi-envelope"></i>
                <div>
                  <h3>Email Us</h3>
                  <p>info@pathways.edu<br>support@pathways.edu</p>
                </div>
              </div>

              <div class="info-item">
                <i class="bi bi-clock"></i>
                <div>
                  <h3>Office Hours</h3>
                  <p>Monday - Friday<br>8:00 AM - 5:00 PM CST</p>
                </div>
              </div>
              
              <div class="info-item">
                <i class="bi bi-lightbulb"></i>
                <div>
                  <h3>Suggest an Opportunity</h3>
                  <p>Know of a great program we're missing? Use the form to suggest it!</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="contact-form-card">
              <h3 style="margin-bottom: 25px; color: #333;">Send Us a Message</h3>
              
              <?php if (isset($_GET['success'])): ?>
              <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Thank you for contacting us! We will get back to you soon.
              </div>
              <?php endif; ?>
              
              <?php if (isset($_GET['error'])): ?>
              <div class="alert alert-error">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
              </div>
              <?php endif; ?>
              
              <form method="POST" action="forms/contact-submit.php">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="name">Your Name <span style="color: red;">*</span></label>
                      <input type="text" id="name" name="name" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="email">Your Email <span style="color: red;">*</span></label>
                      <input type="email" id="email" name="email" required>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label for="inquiry_type">Type of Inquiry <span style="color: red;">*</span></label>
                  <select id="inquiry_type" name="inquiry_type" required onchange="toggleOpportunityFields()">
                    <option value="general">General Question</option>
                    <option value="support">Technical Support</option>
                    <option value="suggest">Suggest New Opportunity</option>
                    <option value="partnership">Partnership Inquiry</option>
                    <option value="feedback">Feedback</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="subject">Subject <span style="color: red;">*</span></label>
                  <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                  <label for="message">Message <span style="color: red;">*</span></label>
                  <textarea id="message" name="message" required></textarea>
                </div>

                <!-- Opportunity Suggestion Fields -->
                <div id="opportunityFields" class="opportunity-fields">
                  <h4><i class="bi bi-plus-circle"></i> Opportunity Details (Optional)</h4>
                  
                  <div class="form-group">
                    <label for="opportunity_name">Opportunity Name</label>
                    <input type="text" id="opportunity_name" name="opportunity_name" placeholder="e.g., National Merit Scholarship">
                  </div>
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="opportunity_state">State</label>
                        <input type="text" id="opportunity_state" name="opportunity_state" placeholder="e.g., Minnesota">
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="opportunity_category">Category</label>
                        <select id="opportunity_category" name="opportunity_category">
                          <option value="">Select Category</option>
                          <option value="Scholarship">Scholarship</option>
                          <option value="Competition">Competition</option>
                          <option value="Club">Club</option>
                          <option value="Academic Program">Academic Program</option>
                          <option value="Program">Program</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label for="opportunity_website">Website URL</label>
                    <input type="url" id="opportunity_website" name="opportunity_website" placeholder="https://example.com">
                  </div>
                </div>

                <button type="submit" class="btn-submit">
                  <i class="bi bi-send"></i> Send Message
                </button>
              </form>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Contact Section -->

    <!-- FAQ Section -->
    <section class="faq-section">
      <div class="container">
        <div class="container section-title" data-aos="fade-up">
          <h2>Frequently Asked Questions</h2>
          <p>Quick answers to common questions</p>
        </div>

        <div class="row">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="faq-item">
              <h4><i class="bi bi-question-circle"></i> How do I suggest a new opportunity?</h4>
              <p>Select "Suggest New Opportunity" from the inquiry type dropdown and fill in the opportunity details. We review all submissions and add verified opportunities to our database.</p>
            </div>
            
            <div class="faq-item">
              <h4><i class="bi bi-question-circle"></i> How long does it take to get a response?</h4>
              <p>We typically respond to all inquiries within 1-2 business days. For urgent matters, please mention it in your message subject.</p>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="faq-item">
              <h4><i class="bi bi-question-circle"></i> Can I partner with Pathways?</h4>
              <p>Yes! We're always interested in partnerships with schools, organizations, and companies. Select "Partnership Inquiry" and tell us about your organization.</p>
            </div>
            
            <div class="faq-item">
              <h4><i class="bi bi-question-circle"></i> How can I report incorrect information?</h4>
              <p>If you find outdated or incorrect opportunity information, please contact us with the details so we can update our database promptly.</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- End FAQ Section -->

  </main>

<?php include('components/footer.php'); ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
  function toggleOpportunityFields() {
    const inquiryType = document.getElementById('inquiry_type').value;
    const opportunityFields = document.getElementById('opportunityFields');
    
    if (inquiryType === 'suggest') {
      opportunityFields.classList.add('show');
    } else {
      opportunityFields.classList.remove('show');
    }
  }
  
  // Initialize AOS
  AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true
  });
  </script>

</body>

</html>