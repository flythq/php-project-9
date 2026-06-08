# Page Analyzer

### Tests and linter status:
[![hexlet-check](https://github.com/flythq/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/flythq/php-project-9/actions/workflows/hexlet-check.yml)
[![check](https://github.com/flythq/php-project-9/actions/workflows/check.yml/badge.svg)](https://github.com/flythq/php-project-9/actions/workflows/check.yml)

[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=flythq_php-project-92&metric=bugs)](https://sonarcloud.io/summary/new_code?id=flythq_php-project-92)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=flythq_php-project-92&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=flythq_php-project-92)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=flythq_php-project-92&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=flythq_php-project-92)

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