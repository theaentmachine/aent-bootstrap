![Aenthill](https://avatars0.githubusercontent.com/u/36076306?s=200&u=77022eb3c9b55b54079c1d41a52f605f42ccaff0&v=4 "Aenthill")

# aent-bootstrap ![Travis](https://camo.githubusercontent.com/5739f743e1f2ece2bae574a0f847335726f55445/68747470733a2f2f7472617669732d63692e6f72672f74686561656e746d616368696e652f61656e742d6d7973716c2e7376673f6272616e63683d6d6173746572 "Travis") ![Scrutinizer](https://camo.githubusercontent.com/9b651623e2d5176a6c89edda1a5c7fe02c975d66/68747470733a2f2f7363727574696e697a65722d63692e636f6d2f672f74686561656e746d616368696e652f61656e742d646f636b65722d636f6d706f73652f6261646765732f7175616c6974792d73636f72652e706e673f623d6d6173746572 "Scrutinizer")

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