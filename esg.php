<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ESG</title>
	<?php include 'head.php';?>

  <style type="text/css">
     .hidden { display: none; }
     html{
        scroll-behavior: smooth;
     }
  </style>
</head>
<body>
	<?php include 'header.php';?>
	<section class="inner-banner-area">
    <img alt="" class="img-fluid mx-auto" src="img/ESG-Slider-1.jpg" />
      <div class="innerpageheading">
          <h2>
          </h2>
      </div>
  </section>
  <section class="breadcrums">
    <div class="custom-container">
      <div class="link-nav">
        <span class="box">
          <a href="index.php" aria-label="home">
            <i class="fa fa-home"></i>
          </a>
          <i class="ti-arrow-right"></i>
          <a href="#">ESG</a> 
          
        </span>
      </div>
    </div>
  </section>

    



  <section class="custom-container">
  <div class="mt-40 mb-40">
    <div class="mb-20">
      <h3 class="mb-3">Our Commitment to Environmental, Social, and Governance (ESG)</h3>
      <p>In today’s rapidly evolving business landscape, Environmental, Social, and Governance (ESG) factors have become more than just considerations of compliance or reputation—they are essential to evaluating a company’s long-term sustainability and ethical impact on society. At DTSS, an SIS Group Enterprise, we understand that integrating ESG into our business strategy is crucial in partnership for building a resilient and successful future.</p>

      <h3 class="mb-3">Our ESG Journey</h3>
      <p>DTSS embarked on a transformative ESG journey in September 2023 to embed sustainability at the very core of its operations. We kickstarted the journey with a comprehensive training program to equip our supply chain stakeholders and employees with the skills for effective data collection, creating strong policy frameworks, and necessary documentation, thereby enabling data-driven decision-making. 
      <br>
      <br>
      We also prioritized the most critical materiality topics and developed clear Key Performance Indicators (KPIs) to reflect our unwavering commitment to ESG. 
      <br>
      <br>
      Today we are happy to share our ESG Report <a href="#submissionForm" >ESG-2024</a> and the Assurance Report by <a download href="data/TUV Assurance Certificate.pdf">TUV</a>. Our  <a href="#submissionForm" >ESG KPIs</a> are carefully crafted to strengthen our relationships with all stakeholders—investors, customers, employees, suppliers, and the communities we serve. These KPIs stand as a testament to our dedication for sustainable and ethical business practices and are aligned with international best practices.
      </p>

      <!-- <h5 class="mb-3">ESG KPIs</h3>
      <p>Our ESG KPIs are carefully crafted to strengthen our relationships with all stakeholders—investors, customers, employees, suppliers, and the communities we serve. These KPIs stand as a testament to our dedication to sustainable and ethical business practices, and we believe they can only be achieved through the active participation of everyone at DTSS.</p> -->
      
      <h1 class=" text-center my-5">Download Our ESG Report</h1>
      
      
      <div class="row d-flex align-items-center py-5" style="background: #f4f4f4;border:2px solid #fcaf17 ; border-radius: 5px;">
        <div class="col-md-2"></div>
        <div class="col-md-4" style="" >
          <img alt="" src="img/dtss-c.jpg" class="img-fluid">
        </div>
        <div class="col-md-4" >
         <h2 class="mb-2">Fill below details to download</h2>
        <form class="form-group" id="submissionForm">
            <input class="form-control mb-3" type="text" id="name" name="name" placeholder="Name" required>
            <input class="form-control mb-3" type="tel" id="phone" name="phone" placeholder="Contact Number"  title="Please enter a 10-digit phone number" required>
            <input class="form-control mb-3" type="email" id="email" name="email" placeholder="Work Email" required>
            <button  id="downloadForm" type="submit" class="btn btn-primary">Submit</button>
        </form>

    <div id="downloadLink" class="hidden">
        <p>Thank you for providing your details! Click the link below to download the ZIP file:</p>
        <a id="downloadAnchor" href="data/ESG_Report_FY_2023-24.zip" download="ESG_Report_FY_2023-24.zip" target="_blank" rel="noopener noreferrer">
            Download ESG Report FY 2023-24
        </a>
    </div>
        </div>
      </div>




      </div>

  </div>
</section>

  


<div class="clearfix "></div>
</div>

<div class="clearfix "></div>



  <?php include 'footer.php';?>
   <script>
        document.getElementById('submissionForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevents the default form submission behavior

            // Hide the form
            document.getElementById('submissionForm').classList.add('hidden');

            // Show the download link
            document.getElementById('downloadLink').classList.remove('hidden');
        });
    </script>
  <script>
        document.getElementById('submissionForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Create FormData object
            const formData = new FormData(this);

            // Send data to PHP script
            fetch('save_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to results page
                    window.location.href = '#downloadLink';
                } else {
                    alert('Error saving data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving data');
            });
        });
    </script>
  <script>
        document.getElementById('downloadForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from submitting the traditional way

            // Validate form
            const name = document.getElementById('name').value;
            const phone = document.getElementById('phone').value;
            const email = document.getElementById('email').value;

            if (name && phone && email) {
                // Hide the form and show the download link
                document.getElementById('downloadForm').classList.add('hidden');
                document.getElementById('downloadLink').classList.remove('hidden');
            } else {
                alert('Please fill in all the required fields.');
            }
        });
    </script>

  <?php include 'footer-link.php';?>

</body>
</html>