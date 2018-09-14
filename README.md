![Aenthill](https://avatars0.githubusercontent.com/u/36076306?s=200&u=77022eb3c9b55b54079c1d41a52f605f42ccaff0&v=4 "Aenthill")

# aent-bootstrap [![Travis CI](https://travis-ci.org/theaentmachine/aent-bootstrap.svg?branch=master "Travis CI")](https://travis-ci.org/theaentmachine/aent-bootstrap) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/theaentmachine/aent-bootstrap/badges/quality-score.png?b=master "Scrutinizer Code Quality")](https://scrutinizer-ci.com/g/theaentmachine/aent-bootstrap/?branch=master)

The aent used by [Aenthill](https://aenthill.github.io) for bootstrapping a Docker project for a web application.

## Usage

```bash
$ aenthill init
```

## Goal

This aent gathers information about the project's environments:

- the type of environments (development, test and production)
- their names
- their base virtual hosts
- their orchestrators (Docker Compose, Kubernetes etc.)
- their CI providers (GitLab etc.)

Those information are next sent to orchestrator aents for helping them initializing their configuration files.

## Documentation

You can find the complete documentation at https://aenthill.github.io.