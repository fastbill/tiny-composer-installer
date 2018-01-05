# Tiny Composer Installer

This is a small, simple and easily auditable tool that downloads [Composer](https://getcomposer.org/), checks its signature and saves it to an executable file. It is designed to be small enough to be committed into your project’s repository to safely bootstrap Composer, which is especially useful in a `Dockerfile`.

## Give me the tl;dr.

As soon as you’ve downloaded `tiny-composer-installer.php`, run `php tiny-composer-installer.php composer.phar` to get the current stable version of Composer saved to `composer.phar`.

When you’re using a `Dockerfile` based on [the official PHP images](https://hub.docker.com/_/php/), you can do it like this:

```dockerfile
COPY tiny-composer-installer.php ./

# If your USER is root, you can install Composer globally.
RUN php tiny-composer-installer.php /usr/local/bin/composer \
 && rm tiny-composer-installer.php
```

## Requirements and limitations

* We haven’t tested this tool in a lot of different environments yet. If it doesn’t work for you, please tell us. However, we don’t aim to support every possible environment.
* [`allow_url_fopen`](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) and the [OpenSSL extension](http://php.net/manual/en/book.openssl.php) need to be available/enabled.
* You need PHP 5.3.2 to run Composer. Tiny Composer Installer doesn’t check for that. The installer itself requires at least PHP 5.2.

## Installation

Get the latest version by simply [downloading tiny-composer-installer.php from here](https://raw.githubusercontent.com/fastbill/tiny-composer-installer/master/tiny-composer-installer.php). The version in `master` should always be production ready. As you’re supposed to read this file to trust it and then commit to your project’s repo, we don’t provide a suggestion to automate this.

## Why do I want this?

* You shouldn’t commit `composer.phar` to your repository. It’s about 2 MB, after all. Instead, you should fetch a current version of it when setting up or building the project.
* You shouldn’t commit [the original installer](https://getcomposer.org/download/) either. It changes less often, but it’s still 300 K in size.
* Neither should you `curl https://getcomposer.org/installer | php`, because you are not checking the signature.
* Fetching the signature from GitHub and then comparing the installer against it, as recommended in [the official docs](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md) is a possibility, but cumbersome. Also, if someone can tamper with the installer download file, they can most likely mess around with the SHA384 signature as well.

Wouldn’t it be nice if the installer wasn’t so large, so you could actually read it, understand it and commit a safe, audited version of it to your project’s repo? This is exactly what Tiny Composer Installer is designed for.

## Security

* This is less than 150 lines of PHP. If you’re not sure whether to trust it, read it. There are no classes or global variables. There aren’t many comments either, but that’s because it’s really rather self-explanatory.
* Before saving the downloaded Composer PHAR, its signature is checked. And not simply against a SHA hash, but against a signature that has been signed with [the public key of the Composer developers](https://composer.github.io/pubkeys.html). That’s the same security check the original installer does.
* About half of these 150 lines is error handling. We didn’t trade size for carelessness.

## Usage

You can pass a destination filename as a parameter. Please note that if the download and signature checks succeed, the file will be overwritten without asking. If you don’t supply a filename, a random one in your system’s temp directory will be generated.

Whether you supplied a parameter or not, when Tiny Composer Installer succeeds it will echo the destination filename to standard output and return with an exit code of zero. On error, stdout will be empty and a non-zero error code will be returned. This allows you to do something like this:

```bash
phar="$(php tiny-composer-installer.php)" && php "$phar" install && rm "$phar"
```

If that’s too fancy for you, this is how you install Composer globally to `/usr/local/bin`.

```bash
sudo php tiny-composer-installer.php /usr/local/bin/composer
```

## FAQ

### How did you get it so small?

* The official installer contains a CA bundle for HTTPS connections. We rely on your system already having one. Actually, since we’re checking the signature against a hardcoded public key, we wouldn’t have to use HTTPS at all.
* The official installer works in a lot of environments and therefore has many checks and fallbacks. We don’t. We assume that you’re using this in an automated toolchain and that you provide a suitable environment.
