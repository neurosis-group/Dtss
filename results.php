<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ESG</title>
    <meta name="keywords" content="" />
   <meta name="title" content="Login Page"/>
  <meta name="description" content="Login Page" />
<link rel="canonical" href="https://dtss.in/login-page" />
	<?php include 'head.php';?>

    <style>
        .container {
            margin-top: 20px;
        }
        .login-form, .data-table {
            display: none;
        }
        .loading-message {
            text-align: center;
            font-style: italic;
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
    



  <div class="container">
        <!-- Login Form -->
        <div class="login-form">
            <h3>Login</h3>
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <div id="loginMessage" class="mt-2"></div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <h3>Fill the Form to Get Reports</h3>
            <table class="table table-striped table-bordered" id="dataTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="loading-message">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const validUsername = 'dtss@login'; // Example username
            const validPassword = 'dtss@pass'; // Example password

            const loginForm = document.querySelector('.login-form');
            const dataTable = document.querySelector('.data-table');
            const loginFormElement = document.getElementById('loginForm');
            const loginMessage = document.getElementById('loginMessage');

            loginForm.style.display = 'block'; // Show login form

            loginFormElement.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent form from submitting normally

                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;

                if (username === validUsername && password === validPassword) {
                    loginForm.style.display = 'none'; // Hide login form
                    dataTable.style.display = 'block'; // Show data table
                    fetchData(); // Fetch data after login
                } else {
                    loginMessage.textContent = 'Invalid username or password';
                    loginMessage.style.color = 'red';
                }
            });

            function fetchData() {
                fetch('data.txt')
                    .then(response => response.text())
                    .then(data => {
                        // Assuming data is in CSV format
                        const rows = data.trim().split('\n');
                        const tableBody = document.querySelector('#dataTable tbody');
                        tableBody.innerHTML = ''; // Clear the loading row

                        if (rows.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="3" class="text-center">No data available</td></tr>';
                            return;
                        }

                        rows.forEach(row => {
                            const columns = row.split(',');
                            const tr = document.createElement('tr');

                            columns.forEach(column => {
                                const td = document.createElement('td');
                                td.textContent = column;
                                tr.appendChild(td);
                            });

                            tableBody.appendChild(tr);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        document.querySelector('#dataTable tbody').innerHTML = '<tr><td colspan="3" class="text-center">Error loading data</td></tr>';
                    });
            }
        });
    </script>

  


<div class="clearfix "></div>
</div>

<div class="clearfix "></div>



  <?php include 'footer.php';?>
   
  <?php include 'footer-link.php';?>

</body>
</html>