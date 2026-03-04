ICS 325-50 Final Project PATHWAYS
Gary Marks

Template Name: Mentor
Template URL: https://bootstrapmade.com/mentor-free-education-bootstrap-theme/
Author: BootstrapMade.com
License: https://bootstrapmade.com/license/

Pathways is a structured, student-centered web application designed to
empower middle and high school learners by connecting them with
meaningful educational and extracurricular opportunities. Through robust
data management, intuitive search, and administrative tools, the project
promotes discovery, engagement, and lifelong learning.

Frontend: HTML5, CSS, Bootstrap, JavaScript, JQuery
Backend: PHP
Database: MySQL
Hosting: Localhost (XAMPP)

Database Layout:
	users
		User_ID, First_Name, Last_Name, Email, Phone, Hash, Active, Role, reset_token_hash, reset_token_expires_at,
		secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active,
		notes, Modified_Time, Created_Time, password_interval_days, password_expires_at, password_changed_at
	
	pathways_opportunities:
		id, display_name, state, program_name, grade_levels, eligibility, cost_funding, deadlines, website_link,
		category, field, delivery_context, notes, created_at, updated_at
		
	saved_opportunities:
		id, user_id, opportunity_id, created_at
		
	contact_submissions:
		id, name, email, inquiry_type, subject, message, opportunity_name, opportunity_state, opportunity_category,
		opportunity_website, status, admin_notes, created_at, updated_at

File Structure:

Mentor
	assets: This file holds assests that came with the bootstrap template from BootsrapMade.com
		css
		img
		js
		scss
		vendor
	components: This file holds the footer, header, and database configuration files
		db-config.php 
		footer.php 
		header.php 
	forms: This file holds the contact form submission php file 
		contact-submit.php
	about.php (The About Page)
	admin-opportunities.php (Admin Opportunity Management Page)
	clubs.php (Page showing all Clubs)
	contact.php (The Contact Page)
	contact-submissions.php (Admin Submissions Management Page)
	edit-profile.php (Handles editing your profile on My Profile Page)
	events.php (Page showing all Programs and Competitions)
	force-password-reset.php (Handles User Login when User Password is expired)
	forgot-password.php (User Forgotten Password Page)
	import-export.php (Admin Opportunities Import/Export database functionality)
	index.php (Home Page)
	login-page.php (The User Login Page)
	logout.php (Handles User Logout)
	my-profile.php (User Profile Page)
	privacy-policy.php (Popout Privacy Policy Page)
	process-forgot-password.php (Handles User Forgotten Password)
	process-login.php (Handles Login Logic)
	process-register.php (Handles entering New User into database)
	process-reset-password.php (Handles password reset logic)
	register-page.php (New User Register Page)
	remove-opportunity.php (Logic to remove opportunity from user favorites)
	reports-summaries.php (Admin Page for opportunity statistics)
	reset-password.php (Reset Password Page)
	scholarships.php (Page showing all Scholarships)
	search-all.php (Page showing ALL Opportunities)
	terms-of-service.php (Popout Page showing Terms of Service)
	toggle-favorite.php (Logic for user favoriting an opportunity)
	usermanagement.php (Admin User Management Page)
	
Details of Iterations:

ICS 325 Internet Application Development (Fall 2025)

Student Name: Gary Marks


FP1: 9/30/25  

My Contribution for this week is: I did Data Collection for Iowa and Ohio and entered the data into the table.


My goals for next week are: Data Collection for California and compiling all the data into one master excel.


--------------------------------------------------------
FP2: 10/07/25  

My Contribution for this week is: Data Collection of last state, labeled National Programs, compiled into single file for submission


My goals for next week are: Site Map of the site, UI Design, index.php


--------------------------------------------------------
FP3: 10/14/25  

My Contribution for this week is: Created SQL table. Site Map/UI Design. Made hand sketches of Design.


My goals for next week are: End User View: Reading the data from "programs" table and displaying the tile view of the programs. Finishing database population.



--------------------------------------------------------
FP4: 10/21/25   

My Contribution for this week is: End User View: The site structure and navigation is up. Logging in and logging out functions were implemented and tested. Reading data from the "programs" table and displaying tile view of the programs. The database was completed and populated. Registration of new accounts and the Account favoriting system was implemented.


My goals for next week are: Admin View- admin capabilities



--------------------------------------------------------

FP5: 10/28/25

My Contribution for this week is: I fixed color coding of opportunities to be uniform. I limited how many opportunities can be viewed per page (20). Implemented Teacher role as admin, allowing access to view admin capabilities once approved by an already existing admin. Implemented User Management capabilities for admin: reset password, change role, delete user, approve teacher admin request.


My goals for next week are: Admin capabilities- add, delete, update opportunities



--------------------------------------------------------
 
FP6: 11/03/2025

My Contribution for this week is: Updated users table in database to match class standardization (added missing fields, changed data types of fields to match and renamed fields to match standard). Troubleshooted existing php files for the changes to the database. Reworked Menu navigation. Implemented footer component to webpages. Expanded password management (added admin settings to apply password policies and force password changes at set intervals of days, implemented forgotten password reset functionality). Added notetaking ability to profile page.


My goals for next week are: Admin capabilities- add, delete, update opportunities, reports, summaries.


--------------------------------------------------------
 
FP7: 11/11/2025

My Contribution for this week is: Added grade level to search filters.  Implemented admin opportunities management (add, edit, delete).  Implemented admin import/export. Began working on cleaning up master list of opportunities.


My goals for next week are: Finish cleaning up master list of opportunities and import. Implement Admin Reports/Summaries. Personalize web pages. Find and fix broken links.



--------------------------------------------------------
  
FP8: 11/18/2025

My Contribution for this week is: Fixed formatting issue on import/export page. Updated Deadline filters. Fixed issue with forced password reset at expiration. Fixed import format issue where deadlines were being set to 0000-00-00. Updated the homepage. Implemented display of recently added or updated opportunities. Updated footer to make more functional and visually appealing. Implemented edit profile from My Profile page. Centralized database configuration. Implemented admin Reports/Summaries with Export options. Updated About page. Updated Contact page. Implemented Contact Submission admin management.


My goals for next week are: Ensure responsive layout for different screen sizes. Update documentation, README.txt. Hunt down any further deadlinks. Further testing to uncover and squash any bugs.



--------------------------------------------------------
FP9: 11/25/2025

My Contribution for this week is:  Fixed text formatting issues on mobile. Made admin functions mobile-responsive. Updated database to fix broken links to individual opportunities pages. Fixed entries where grade level entered as dates. Fixed entries with missing information. Created the terms of service and privacy policy pages.


My goals for next week are: Update Documentation and README.txt file. Continue to test and debug. Polishing touches.



--------------------------------------------------------
FP10: 12/02/2025

My Contribution for this week is: Update Documentation and README.txt file. Testing and Debugging, Polishing touches.


--------------------------------------------------------