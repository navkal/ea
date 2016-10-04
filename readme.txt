How to deploy Energize Apps website:

- Install Anaconda Python

- Create or choose a deployment directory <deployment path>

- Download "ea" and "common" projects from GitHub to <deployment path>

- In httpd.conf, define a virtual host with the following
  . SetEnv PYTHON "<path to Anaconda Python executable>"
  . DocumentRoot "<deployment path>/ea"

- Modify settings in php.ini
  . max_execution_time=300
  . memory_limit=512M
  . post_max_size=500M
  . upload_max_filesize=500M

- On Linux, install optional ZipArchive package
  . sudo apt-get install php7.0-zip

- Restart apache2
  . sudo service apache2 restart
