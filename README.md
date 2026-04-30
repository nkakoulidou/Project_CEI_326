Project CEI_326

Μέλη ομάδας: Νικολέτα Κακουλίδου 30427, Έλενα Σαριχανί 30752

Νικολέτα Κακουλίδου:Candidate module,API module
'Ελενα Σαριχανί:Admin module,Search module

# Project CEI 326 - Instructions

##  Overview
This project is designed to run on a PHP-based backend environment and can be executed locally using WSL (Windows Subsystem for Linux). It uses a MySQL-compatible database and a lightweight PHP development server.

## Requirements
Before running the project, ensure you have the following installed:

- Windows Subsystem for Linux (WSL)
- Ubuntu (or another Linux distribution on WSL)
- PHP (>= 7.4 recommended)
- MySQL (or compatible database)
- Visual Studio Code
- VS Code WSL Extension

## Installation

### 1. Install WSL Extension in VS Code
Open Visual Studio Code and install the **WSL extension** from the Extensions Marketplace.


### 2. Open Project in WSL
Clone or move the project into your WSL environment and navigate to:

/root/workdir/

If the folder does not exist, create it:

mkdir -p /root/workdir/


### 3. Update System Packages
Run:

sudo apt update


### 4. Install PHP and MySQL Extension
Install required dependencies:

sudo apt install php php-mysql -y


##  Run the Project
Start the PHP built-in development server:

php -S localhost:8000


##  Access the Application
Open your browser and go to:

http://localhost:8000



##  Notes
- Make sure WSL is properly installed and running before executing any commands.
- Ensure MySQL service is running if database functionality is required.
- If port 8000 is already in use, you can change it (e.g. 8001).
