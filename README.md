## OpenPix for Magento2
OpenPix Magento2 Pix plugin

## Development
To develop and test this Plugin in you need to have a Magento installation in your machine.
Create a new empty folder and follow the automated setup

## Adding OpenPix Magento Plugin to your installation

```bash
mkdir src/app/code/OpenPix
cd src/app/code/OpenPix
git clone https://github.com/Open-Pix/magento2-openpix.git
```


## First Time
The best way to go forward with Magento2 local development is by [docker-magento](https://github.com/markshust/docker-magento).

Open the repo and use the **Automated Setup (New Project)** to prepare the local develop environment as it is listed below:

###  Automated setup as on `docker-magento`
- go to Magento [Marketplace](https://marketplace.magento.com/) and sign in or create a new account
- Log in and go to https://marketplace.magento.com/customer/accessKeys/ by clickin in `MyProfile`
  ![img.png](./docs/login.png)

- Go to `Access Keys`
  ![img_1.png](./docs/accesskey.png)

- create new access key and save both values: public and private
  ![img_2.png](./docs/keys.png)

### on terminal
- go to the root folder
- run `curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test 2.4.2`
- pay attention into `magento.test`. This will be your local route

- it will be requested a `Username`. Here, is where you copy and paste the public key from magento marketplace
- after, it will be requested the password, do the same for the private key

- will update dependencies
- config the local store
- open in `magento.test` or the name you choose

## creating admin user
- on terminal run `bin/magento admin:user:create`
- fill information
- open `magento.test/admin` on browser and do the login

### disable two factor authenticator
after creating the admin user it will be request the two factor authenticator. Disable it running this:

- on terminal at the root of project:
```ts
bin/magento module:disable Magento_TwoFactorAuth
bin/magento setup:upgrade
```

## Local development already prepared
Once the local development is already ok you can start to use the bin/magento CLI to run the application with OpenPix Magento2 PixPlugin.

### Docker
Make sure docker is running and active

#### Arch Linux
- **sudo systemctl status docker** - status docker
- **sudo systemctl start docker**  - start docker
- **sudo systemctl stop docker**   - start docker

### Start/Stop
**bin/start** it will start all containers for your application
**bin/stop** it will stop all containers for your application

## How to Develop and install
Sources to be helpful to this step:
- [openpix magento plugin #21321](https://github.com/entria/feedback-server/issues/21321) issue with docs Sources
- [docker commands gist](https://github.com/entria/feedback-server/issues/21321) helpful commands for docker
- [bin/magento commands gist](https://gist.github.com/daniloab/da0e4928ecc0aca5d71380b96425aff1) helpful commands for bin/magento cli

## Installing the plugin inside Magento2 store
- go to magento2 store src/
- inside of `src/app/code/` create a new folder with vendor name `Vendor` ex: OpenPix
- inside of `OpenPix` create other folder and name as module anme `ModuleName` ex: Pix

### Updating the Magento2 store after change
- make sure you store containers are running
- run `bin/magento setup:upgrade` to update it
- refresh your store to see the changes


## Common Errors

### Port 80 is used by some process
You have something running on port 80 (this happens on mac osx)

Disable apache server

```bash
sudo apachectl stop
```

### Your Magento authentication keys are invalid
If you registered the wrong magento credentails, you can change then later on at ~/.composer/auth.json
