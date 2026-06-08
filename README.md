# Page Analyzer

### Tests and linter status:
[![hexlet-check](https://github.com/flythq/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/flythq/php-project-9/actions/workflows/hexlet-check.yml)
[![check](https://github.com/flythq/php-project-9/actions/workflows/check.yml/badge.svg)](https://github.com/flythq/php-project-9/actions/workflows/check.yml)

## About

This is a site that analyzes the specified pages for SEO suitability

**See the web service demo:** [Page Analyzer](https://php-project-9-comh.onrender.com).

## Prerequisites

+ Linux, MacOS, WSL
+ PostgreSQL >= 16.9
+ PHP >=8.4
+ Composer
+ Make
+ Git


## Usage

### Install project

This command installs PHP dependencies via Composer and sets up the environment:

```bash
git clone https://github.com/flythq/php-project-9
cd page-analyzer
make install
```

### Create file with environment variables

Create an `.env` file and specify in it your data
to connect to the **PostgreSQL** database:

```dotenv
DATABASE_URL=postgresql://username:password@host:port/dbname
```

### Start server

Finally, start your **PHP Server**:

```bash
make start
```

Open in browser: http://localhost:8000