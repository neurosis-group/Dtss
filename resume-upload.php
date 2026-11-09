<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8">
	
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Upload Your Resume | Careers at Duster Total Solutions Services</title>
	<meta name="keywords" content="" />
   <meta name="title" content="Upload Your Resume | Careers at Duster Total Solutions Services"/>
  <meta name="description" content="Join Duster Total Solutions Services by uploading your resume today! Explore exciting career opportunities in facility management and apply for positions like Warehouse Manager, Software Engineer, and more." />
<link rel="canonical" href="https://dtss.in/resume-upload" />
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'header.php';?>
	


<style type="text/css">
  .parsley-required, .parsley-type, .parsley-pattern, .parsley-minlength { color:#d93025; font-size:12px; }
  .parsley-errors-list { list-style:none; padding:4px 0 0; margin:0; }
  .resume-form-wrap { max-width:900px; margin:0 auto; }
  .resume-form-wrap .form-group { margin-bottom:18px; }
  .resume-form-wrap select.common-input { width:100%; height:42px; border:1px solid #ddd; border-radius:0; padding:0 14px; font-size:13px; color:#555; text-transform:uppercase; background:#fff; -webkit-appearance:auto; appearance:auto; }
  .resume-form-wrap .nice-select { display:none !important; }
  .resume-file { display:block; width:100%; margin:0; position:relative; border:1px dashed #b9bfc9; background:#fafbfc; padding:22px 16px; text-align:center; cursor:pointer; transition:border-color .2s, background .2s; }
  .resume-file:hover, .resume-file.has-file { border-color:#f9b218; background:#fffaf0; }
  .resume-file input[type=file] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
  .resume-file .rf-title { font-size:14px; font-weight:600; color:#1d2b4f; margin:0; }
  .resume-file .rf-hint { font-size:12px; color:#777; margin:4px 0 0; }
  .resume-file .rf-name { font-size:13px; color:#1d2b4f; margin:8px 0 0; word-break:break-all; }
  .resume-form-wrap .form-note { font-size:12px; color:#777; text-align:center; margin-top:10px; }
</style>


  <!-- ================ start banner Area ================= -->

  <section class="inner-banner-area"> <img alt="resume-uplaod-banner" src="img/resume-uplaod-banner.jpg" class="img-fluid mx-auto">

    <div class="innerpageheading">

      <h2>Upload Resume</h2>

      <!-- <p>If you think we might be a good fit for a project you’re working on and you’d like to get the feel of how we can work together please contact us using contact data.</p> -->

    </div>

  </section>

  <!-- ================ End banner Area ================= -->

  <section class="breadcrums">
    <div class="custom-container">
      <div class="link-nav"> 
      <span class="box">
             <a href="index.php" aria-label="home"><i class="fa fa-home"></i></a>
             <i class="ti-arrow-right"></i>
              <a href="#">Upload Resume</a>
           </span> </div>

    </div>

  </section>

  <!-- ================ Start resume form Area ================= -->

  <section class="contact-page-area section-gap">
    <div class="container">
      <div class="resume-form-wrap">
        <form id="resumeForm" data-parsley-validate method="POST" action="save-resume.php" class="form-area contact-form" enctype="multipart/form-data">

          <!-- honeypot: humans never see this -->
          <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-lg-6 form-group">
              <input type="text" name="name" placeholder="YOUR NAME *" class="common-input form-control" required maxlength="120" data-parsley-required-message="Please enter your name">
            </div>
            <div class="col-lg-6 form-group">
              <input type="tel" name="phone" placeholder="PHONE *" class="common-input form-control" required data-parsley-pattern="^[+]?[0-9 -]{10,15}$" data-parsley-pattern-message="Please enter a valid phone number" data-parsley-required-message="Please enter your phone number">
            </div>
          </div>

          <div class="row">
            <div class="col-lg-6 form-group">
              <input type="email" name="email" placeholder="EMAIL *" class="common-input form-control" required data-parsley-required-message="Please enter your email">
            </div>
            <div class="col-lg-6 form-group">
              <select name="position" class="common-input form-control" required data-parsley-required-message="Please select a position">
                <option value="">POSITION YOU ARE APPLYING FOR *</option>
                <option value="Warehouse Manager">Warehouse Manager</option>
                <option value="Business Development">Business Development</option>
                <option value="Software Engineer">Software Engineer</option>
                <option value="Transport Executive">Transport Executive</option>
                <option value="Purchase Manager">Purchase Manager</option>
                <option value="Project Manager">Project Manager</option>
                <option value="Assistant Manager">Assistant Manager</option>
                <option value="Marketing">Marketing</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12 form-group">
              <label class="resume-file" id="resumeDrop">
                <input type="file" name="resume" id="resumeFile" accept=".pdf,.doc,.docx" required data-parsley-required-message="Please attach your resume">
                <p class="rf-title">Upload your resume *</p>
                <p class="rf-hint">PDF, DOC or DOCX, up to 5 MB</p>
                <p class="rf-name" id="resumeFileName"></p>
              </label>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12 form-group">
              <textarea placeholder="MESSAGE (OPTIONAL)" name="message" class="common-textarea form-control" maxlength="2000"></textarea>
            </div>
          </div>

          <div class="row pt-2">
            <div class="col-lg-12 text-center">
              <button type="submit" class="btn">SUBMIT</button>
              <p class="form-note">Your details are used only for recruitment at DTSS.</p>
            </div>
          </div>

        </form>
      </div>
    </div>
  </section>

  <!-- ================ End resume form Area ================= -->



  <?php include 'footer.php';?>

  <?php include 'footer-link.php';?>


<script>
(function(){
  var input = document.getElementById('resumeFile');
  var box = document.getElementById('resumeDrop');
  var label = document.getElementById('resumeFileName');
  if (!input) return;
  input.addEventListener('change', function(){
    var f = input.files && input.files[0];
    if (!f) { label.textContent=''; box.classList.remove('has-file'); return; }
    var ok = /\.(pdf|docx?)$/i.test(f.name);
    if (!ok) { alert('Please upload a PDF, DOC or DOCX file.'); input.value=''; label.textContent=''; box.classList.remove('has-file'); return; }
    if (f.size > 5*1024*1024) { alert('File is larger than 5 MB. Please upload a smaller file.'); input.value=''; label.textContent=''; box.classList.remove('has-file'); return; }
    label.textContent = 'Selected: ' + f.name;
    box.classList.add('has-file');
  });
  if (window.jQuery) {
    jQuery(function($){ try { $('#resumeForm select').niceSelect && $('#resumeForm select').niceSelect('destroy'); } catch(e){} });
  }
})();
</script>

</body>
</html>