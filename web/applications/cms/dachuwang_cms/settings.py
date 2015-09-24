#coding: utf-8
"""
Django settings for dachuwang_cms project.

For more information on this file, see
https://docs.djangoproject.com/en/1.7/topics/settings/

For the full list of settings and their values, see
https://docs.djangoproject.com/en/1.7/ref/settings/
"""

# Build paths inside the project like this: os.path.join(BASE_DIR, ...)
import os
import exec_env_conf

BASE_DIR = os.path.dirname(os.path.dirname(__file__))
exec_env = os.environ['EXEC_ENV']


# Quick-start development settings - unsuitable for production
# See https://docs.djangoproject.com/en/1.7/howto/deployment/checklist/

# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = 'arh36(077*(d#u!fm=b^wgdd13#6c9yqfn)za$&+^of$3mnnjz'

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = True

TEMPLATE_DEBUG = True

ALLOWED_HOSTS = []

TEMPLATE_DIRS = (
	os.path.join(BASE_DIR, "templates"),
)


# Application definition

INSTALLED_APPS = (
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
    'anti_products',
)

MIDDLEWARE_CLASSES = (
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.locale.LocaleMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.auth.middleware.SessionAuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
)

ROOT_URLCONF = 'dachuwang_cms.urls'

WSGI_APPLICATION = 'dachuwang_cms.wsgi.application'


# Database
# https://docs.djangoproject.com/en/1.7/ref/settings/#databases

DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': os.path.join(BASE_DIR, 'db.sqlite3'),
    },
    'anti_products': {
        'ENGINE': 'django.db.backends.mysql',
        'NAME': 'd_dachuwang',
        'USER': 'ecun',
        'PASSWORD': 'ecun001',
        'HOST': exec_env_conf.conf[exec_env]['db_host'],
    },
}

# Internationalization
# https://docs.djangoproject.com/en/1.7/topics/i18n/

LANGUAGE_CODE = 'zh-cn'

LANGUAGES = (
    ('zh-cn', u'简体中文'), # instead of 'zh-CN'
    ('zh-tw', u'繁體中文'), # instead of 'zh-TW'
)

TIME_ZONE = 'Asia/Shanghai'

USE_I18N = True

USE_L10N = True

USE_TZ = False


# Static files (CSS, JavaScript, Images)
# https://docs.djangoproject.com/en/1.7/howto/static-files/

STATIC_ROOT = os.path.join(BASE_DIR, "static")
STATIC_URL = '/cmsstatic/'
DATABASE_ROUTERS = ['dbrouter.AntiProductsRouter', ]
AUTHENTICATION_BACKENDS = ['django.contrib.auth.backends.ModelBackend', ]
