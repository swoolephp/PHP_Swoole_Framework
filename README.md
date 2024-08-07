# PHP Swoole Framework

A high-performance PHP framework using Swoole for building web applications, network servers, and real-time systems.

## Features

- **High Performance**: Built on Swoole, providing asynchronous and coroutine support.
- **HTTP/HTTPS Server**: Handles both HTTP and HTTPS requests natively.
- **Database Pool**: Efficiently manages MySQL connections with a connection pool.
- **Redis Pool**: Efficiently manages Redis connections with a connection pool.
- **Coroutine Support**: Leverages Swoole coroutines for concurrent processing.

## Requirements

- Docker
- Docker Compose

## Installation

### Install Docker

#### On Linux

1. **Update your package index:**

    ```bash
    sudo apt-get update
    ```

2. **Install packages to allow apt to use a repository over HTTPS:**

    ```bash
    sudo apt-get install \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        lsb-release
    ```

3. **Add Docker’s official GPG key:**

    ```bash
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    ```

4. **Set up the stable repository:**

    ```bash
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    ```

5. **Install Docker Engine:**

    ```bash
    sudo apt-get update
    sudo apt-get install docker-ce docker-ce-cli containerd.io
    ```

6. **Verify that Docker Engine is installed correctly:**

    ```bash
    sudo docker run hello-world
    ```

#### On Windows

1. **Download Docker Desktop for Windows from [Docker Hub](https://hub.docker.com/editions/community/docker-ce-desktop-windows/).**

2. **Run the Docker Desktop Installer and follow the on-screen instructions.**

3. **Once installation is complete, launch Docker Desktop.**

4. **Verify the installation by opening a command prompt and running:**

    ```bash
    docker --version
    ```

### Install Docker Compose

#### On Linux

1. **Download the current stable release of Docker Compose:**

    ```bash
    sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    ```

2. **Apply executable permissions to the binary:**

    ```bash
    sudo chmod +x /usr/local/bin/docker-compose
    ```

3. **Verify the installation:**

    ```bash
    docker-compose --version
    ```

#### On Windows

Docker Compose is included in Docker Desktop for Windows, so you don't need to install it separately. Verify the installation by opening a command prompt and running:

```bash
docker-compose --version

## Build Project on Docker
    sudo docker-compose build
    sudo docker-compose up -d
    sudo docker-compose ps

### Restart service PHP when update script php
    sudo docker-compose restart php

### View ip of Redis, MySql of Docker local for edit config/config.php
    sudo docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' redis
    sudo docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' mysql
    sudo sudo truncate -s 0 /var/lib/docker/containers/*/*-json.log