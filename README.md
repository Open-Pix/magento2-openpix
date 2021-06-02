## OpenPix for Magento2
OpenPix Magento2 Pix plugin

## How to Develop and install

### First time preparing development environment
The best way to go forward with Magento2 local development is by [docker-magento](https://github.com/markshust/docker-magento).

Open the repo and use the **Automated Setup (New Project)** to prepare the local develop environment.

Sources to be helpful to this step:
- [openpix magento plugin #21321](https://github.com/entria/feedback-server/issues/21321) issue with docs Sources
- [docker commands gist](https://github.com/entria/feedback-server/issues/21321) helpful commands for docker
- [bin/magento commands gist](https://gist.github.com/daniloab/da0e4928ecc0aca5d71380b96425aff1) helpful commands for bin/magento cli

## Installing the plugin inside Magento2 store
- go to magento2 store src/
- inside of `src/app/code/` create a new folder `OpenPix`
- inside of `OpenPix` create other folder and name as `Pix`
- clone this repo
- copy and paste the folder `./plugin/Pix` content inside of `/src/app/code/OpenPix/Pix` in your Magento2 local store

### Updating the Magento2 store
- make sure you store containers are running
- run `bin/magento setup:upgrade` to update it
- refresh your store to see the changes

## Local development already prepared
Once the local development is already ok you can start to use the bin/magento CLI to run the application with OpenPix Magento2 PixPlugin.

### Docker
Make sure docker is running and active

#### Arch Linux
**sudo systemctl status docker** - status docker
**sudo systemctl start docker**  - start docker
**sudo systemctl stop docker**   - start docker

### Start/Stop
**bin/start** it will start all containers for your application
**bin/stop** it will stop all containers for your application