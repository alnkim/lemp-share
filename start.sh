#!/bin/bash
echo "Starting Cosc 428 Project..."
sudo mount -t vboxsf lemp-share /home/ubuntu/lemp-project 2>/dev/null
cd /home/ubuntu/lemp-project
sudo docker compose up -d
echo "Docker containers started."
echo "Your VM IP Address is:"
hostname -I
echo "Access the app at http://<YOUR-IP-HERE>/"
echo "Access the health API at http://<YOUR-IP-HERE>/health.php"