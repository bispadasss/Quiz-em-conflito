<?php

declare(strict_types=1);

session_start();

const ROOT_PATH = __DIR__ . '/..';
const BASE_URL = '/Quiz/';
const USERS_FILE = ROOT_PATH . '/data/users.json';
const QUESTIONS_FILE = ROOT_PATH . '/data/questions.json';

const QUESTIONS_PER_GAME = 10;
const NAME_MIN_LENGTH = 3;
const NAME_MAX_LENGTH = 30;
const PASSWORD_MIN_LENGTH = 6;

require_once __DIR__ . '/json.php';
require_once __DIR__ . '/collection.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ranking.php';
require_once __DIR__ . '/questions.php';
require_once __DIR__ . '/game.php';
require_once __DIR__ . '/timer.php';
require_once __DIR__ . '/result.php';
