<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");
ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


class ProductLoanRequestException extends RuntimeException
{
    private $responseStatus;

    public function __construct(
        string $message,
        int $responseStatus = 422
    ) {
        parent::__construct($message);
        $this->responseStatus = $responseStatus;
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }
}


function productLoanSaveResponse(
    bool $success,
    string $message,
    array $data = [],
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit();
}


function productLoanNormalizeText($value): string
{
    $value = trim((string) $value);

    $normalized = preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    return $normalized !== null
        ? $normalized
        : $value;
}


function productLoanTextLength(string $value): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}


function productLoanMoneyToCents($value): int
{
    if (!is_numeric($value)) {
        throw new ProductLoanRequestException(
            "A product contains an invalid price."
        );
    }

    $amount = (float) $value;

    if (!is_finite($amount) || $amount <= 0) {
        throw new ProductLoanRequestException(
            "Product prices must be greater than zero."
        );
    }

    if ($amount > 9999999999.99) {
        throw new ProductLoanRequestException(
            "A product price exceeds the allowed amount."
        );
    }

    return (int) round($amount * 100);
}


function productLoanCentsToAmount(int $cents): float
{
    return round($cents / 100, 2);
}


function productLoanLimitFileName(string $fileName): string
{
    $fileName = basename($fileName);

    $fileName = preg_replace(
        '/[\x00-\x1F\x7F]/u',
        '',
        $fileName
    );

    $fileName = trim((string) $fileName);

    if ($fileName === '') {
        $fileName = 'attachment';
    }

    return function_exists('mb_substr')
        ? mb_substr(
            $fileName,
            0,
            240,
            'UTF-8'
        )
        : substr(
            $fileName,
            0,
            240
        );
}


function productLoanSanitizeStoredBaseName(
    string $originalFileName
): string {
    $baseName = (string) pathinfo(
        basename($originalFileName),
        PATHINFO_FILENAME
    );

    $baseName = preg_replace(
        '~[\x00-\x1F\x7F<>:"/\\\\|?*]+~u',
        ' ',
        $baseName
    );

    $baseName = preg_replace(
        '/\s+/u',
        ' ',
        (string) $baseName
    );

    $baseName = trim(
        (string) $baseName,
        " .\t\n\r\0\x0B"
    );

    $reservedNames = [
        'con',
        'prn',
        'aux',
        'nul',
        'com1',
        'com2',
        'com3',
        'com4',
        'com5',
        'com6',
        'com7',
        'com8',
        'com9',
        'lpt1',
        'lpt2',
        'lpt3',
        'lpt4',
        'lpt5',
        'lpt6',
        'lpt7',
        'lpt8',
        'lpt9'
    ];

    if (
        $baseName === '' ||
        in_array(
            strtolower($baseName),
            $reservedNames,
            true
        )
    ) {
        $baseName = 'attachment';
    }

    $baseName = function_exists('mb_substr')
        ? mb_substr(
            $baseName,
            0,
            160,
            'UTF-8'
        )
        : substr(
            $baseName,
            0,
            160
        );

    $baseName = rtrim(
        $baseName,
        " ."
    );

    return $baseName !== ''
        ? $baseName
        : 'attachment';
}


function productLoanCreateReadableStoredFileName(
    string $originalFileName,
    string $extension,
    string $directory,
    array &$reservedFileNames
): string {
    $safeBaseName =
        productLoanSanitizeStoredBaseName(
            $originalFileName
        );

    $counter = 1;

    do {
        $suffix = $counter === 1
            ? ''
            : ' (' . $counter . ')';

        $candidate =
            $safeBaseName .
            $suffix .
            '.' .
            strtolower($extension);

        $candidateKey =
            strtolower($candidate);

        $alreadyReserved =
            isset(
                $reservedFileNames[
                    $candidateKey
                ]
            );

        $alreadyExists =
            file_exists(
                $directory .
                DIRECTORY_SEPARATOR .
                $candidate
            );

        $counter++;
    } while (
        $alreadyReserved ||
        $alreadyExists
    );

    $reservedFileNames[
        $candidateKey
    ] = true;

    return $candidate;
}


function productLoanFileExtension(
    string $fileName
): string {
    return strtolower(
        (string) pathinfo(
            basename($fileName),
            PATHINFO_EXTENSION
        )
    );
}


function productLoanHasDangerousEmbeddedExtension(
    string $fileName
): bool {
    return preg_match(
        '/\.(php[0-9]*|phtml|phar|cgi|pl|py|sh|exe|com|bat|cmd|msi|js|html?|svg|sql)(?:\.|$)/i',
        basename($fileName)
    ) === 1;
}


function productLoanFileStartsWith(
    string $filePath,
    string $expectedBytes
): bool {
    $handle = @fopen(
        $filePath,
        'rb'
    );

    if (!$handle) {
        return false;
    }

    try {
        $actualBytes = fread(
            $handle,
            strlen($expectedBytes)
        );
    } finally {
        fclose($handle);
    }

    return is_string($actualBytes) &&
        hash_equals(
            $expectedBytes,
            $actualBytes
        );
}


function productLoanIsValidLegacyDoc(
    string $filePath
): bool {
    if (
        !productLoanFileStartsWith(
            $filePath,
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
        )
    ) {
        return false;
    }

    $contents = @file_get_contents(
        $filePath
    );

    if (!is_string($contents)) {
        return false;
    }

    $wordDocumentStream =
        "W\x00o\x00r\x00d\x00D\x00o\x00c\x00u\x00m\x00e\x00n\x00t\x00";

    return strpos(
        $contents,
        $wordDocumentStream
    ) !== false;
}


function productLoanIsValidDocx(
    string $filePath
): bool {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException(
            'The PHP ZIP extension is unavailable.'
        );
    }

    $archive = new ZipArchive();

    $opened = $archive->open(
        $filePath,
        ZipArchive::RDONLY
    );

    if ($opened !== true) {
        return false;
    }

    try {
        $requiredEntries = [
            '[Content_Types].xml',
            '_rels/.rels',
            'word/document.xml'
        ];

        foreach (
            $requiredEntries as $entryName
        ) {
            if (
                $archive->locateName(
                    $entryName
                ) === false
            ) {
                return false;
            }
        }

        $contentTypeStat =
            $archive->statName(
                '[Content_Types].xml'
            );

        if (
            !is_array($contentTypeStat) ||
            (int) (
                $contentTypeStat['size']
                ?? 0
            ) <= 0 ||
            (int) (
                $contentTypeStat['size']
                ?? 0
            ) > 1024 * 1024
        ) {
            return false;
        }

        $contentTypes =
            $archive->getFromName(
                '[Content_Types].xml'
            );

        return is_string($contentTypes) &&
            stripos(
                $contentTypes,
                'wordprocessingml.document.main+xml'
            ) !== false;
    } finally {
        $archive->close();
    }
}


function productLoanNormalizeUploads(
    string $fieldName
): array {
    if (
        !isset($_FILES[$fieldName]) ||
        !isset(
            $_FILES[$fieldName]['name']
        )
    ) {
        return [];
    }

    $files = $_FILES[$fieldName];
    $normalized = [];

    if (is_array($files['name'])) {
        $count = count($files['name']);

        for (
            $index = 0;
            $index < $count;
            $index++
        ) {
            $error =
                isset(
                    $files['error'][$index]
                )
                    ? (int)
                        $files['error'][$index]
                    : UPLOAD_ERR_NO_FILE;

            if (
                $error ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $normalized[] = [
                'name' =>
                    $files['name'][$index]
                    ?? '',

                'tmp_name' =>
                    $files['tmp_name'][$index]
                    ?? '',

                'error' =>
                    $error,

                'size' =>
                    isset(
                        $files['size'][$index]
                    )
                        ? (int)
                            $files['size'][$index]
                        : 0
            ];
        }

        return $normalized;
    }

    if (
        (int) $files['error'] !==
        UPLOAD_ERR_NO_FILE
    ) {
        $normalized[] = [
            'name' =>
                $files['name']
                ?? '',

            'tmp_name' =>
                $files['tmp_name']
                ?? '',

            'error' =>
                isset($files['error'])
                    ? (int) $files['error']
                    : UPLOAD_ERR_NO_FILE,

            'size' =>
                isset($files['size'])
                    ? (int) $files['size']
                    : 0
        ];
    }

    return $normalized;
}


function productLoanUploadErrorMessage(
    int $error
): string {
    switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return
                'An attachment exceeds the server upload limit.';

        case UPLOAD_ERR_PARTIAL:
            return
                'An attachment was only partially uploaded.';

        case UPLOAD_ERR_NO_TMP_DIR:
            return
                'The temporary upload folder is unavailable.';

        case UPLOAD_ERR_CANT_WRITE:
            return
                'The server could not write an attachment.';

        case UPLOAD_ERR_EXTENSION:
            return
                'A server extension stopped an attachment upload.';

        default:
            return
                'An attachment could not be uploaded.';
    }
}


function productLoanValidateUploads(
    array $files
): array {
    $maximumAttachments = 10;

    $maximumFileSize =
        10 * 1024 * 1024;

    $maximumCombinedSize =
        50 * 1024 * 1024;

    $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
        'doc',
        'docx'
    ];

    if (
        count($files) >
        $maximumAttachments
    ) {
        throw new ProductLoanRequestException(
            'A maximum of 10 attachments is allowed per upload.'
        );
    }

    if (count($files) === 0) {
        throw new ProductLoanRequestException(
            'At least one supporting document is required before saving the Product Loan application.'
        );
    }

    if (!function_exists('finfo_open')) {
        throw new RuntimeException(
            'The PHP Fileinfo extension is unavailable.'
        );
    }

    $fileInfo = finfo_open(
        FILEINFO_MIME_TYPE
    );

    if (!$fileInfo) {
        throw new RuntimeException(
            'Unable to inspect uploaded files.'
        );
    }

    $validatedFiles = [];
    $totalSize = 0;
    $uploadHashes = [];

    try {
        foreach ($files as $file) {
            if (
                (int) $file['error'] !==
                UPLOAD_ERR_OK
            ) {
                throw new ProductLoanRequestException(
                    productLoanUploadErrorMessage(
                        (int) $file['error']
                    )
                );
            }

            $fileSize =
                (int) $file['size'];

            if ($fileSize <= 0) {
                throw new ProductLoanRequestException(
                    'An attachment is empty.'
                );
            }

            if (
                $fileSize >
                $maximumFileSize
            ) {
                throw new ProductLoanRequestException(
                    'Each attachment must not exceed 10 MB.'
                );
            }

            $totalSize += $fileSize;

            if (
                $totalSize >
                $maximumCombinedSize
            ) {
                throw new ProductLoanRequestException(
                    'The combined attachment size must not exceed 50 MB per upload.'
                );
            }

            $temporaryPath =
                (string) $file['tmp_name'];

            $originalName =
                productLoanLimitFileName(
                    (string) $file['name']
                );

            $originalExtension =
                productLoanFileExtension(
                    $originalName
                );

            if (
                !in_array(
                    $originalExtension,
                    $allowedExtensions,
                    true
                )
            ) {
                throw new ProductLoanRequestException(
                    'Only JPG, PNG, PDF, DOC, and DOCX attachments are allowed.'
                );
            }

            if (
                productLoanHasDangerousEmbeddedExtension(
                    $originalName
                )
            ) {
                throw new ProductLoanRequestException(
                    'An unsafe attachment filename was detected.'
                );
            }

            if (
                !is_uploaded_file(
                    $temporaryPath
                )
            ) {
                throw new ProductLoanRequestException(
                    'An invalid attachment upload was detected.',
                    400
                );
            }

            $detectedMimeType =
                finfo_file(
                    $fileInfo,
                    $temporaryPath
                );

            if (
                !is_string(
                    $detectedMimeType
                )
            ) {
                throw new ProductLoanRequestException(
                    "Unable to identify an attachment's real file type."
                );
            }

            $storedExtension = '';
            $canonicalMimeType = '';

            switch ($originalExtension) {
                case 'jpg':
                case 'jpeg':
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $detectedMimeType !==
                            'image/jpeg' ||
                        !is_array(
                            $imageDetails
                        ) ||
                        (
                            $imageDetails['mime']
                            ?? ''
                        ) !== 'image/jpeg'
                    ) {
                        throw new ProductLoanRequestException(
                            'A JPG attachment does not contain a valid JPG image.'
                        );
                    }

                    $storedExtension = 'jpg';
                    $canonicalMimeType =
                        'image/jpeg';
                    break;

                case 'png':
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $detectedMimeType !==
                            'image/png' ||
                        !is_array(
                            $imageDetails
                        ) ||
                        (
                            $imageDetails['mime']
                            ?? ''
                        ) !== 'image/png'
                    ) {
                        throw new ProductLoanRequestException(
                            'A PNG attachment does not contain a valid PNG image.'
                        );
                    }

                    $storedExtension = 'png';
                    $canonicalMimeType =
                        'image/png';
                    break;

                case 'pdf':
                    if (
                        !in_array(
                            $detectedMimeType,
                            [
                                'application/pdf',
                                'application/x-pdf'
                            ],
                            true
                        ) ||
                        !productLoanFileStartsWith(
                            $temporaryPath,
                            '%PDF-'
                        )
                    ) {
                        throw new ProductLoanRequestException(
                            'A PDF attachment does not contain a valid PDF document.'
                        );
                    }

                    $storedExtension = 'pdf';
                    $canonicalMimeType =
                        'application/pdf';
                    break;

                case 'doc':
                    if (
                        !in_array(
                            $detectedMimeType,
                            [
                                'application/msword',
                                'application/x-ole-storage',
                                'application/CDFV2',
                                'application/octet-stream'
                            ],
                            true
                        ) ||
                        !productLoanIsValidLegacyDoc(
                            $temporaryPath
                        )
                    ) {
                        throw new ProductLoanRequestException(
                            'A DOC attachment does not contain a valid Word document.'
                        );
                    }

                    $storedExtension = 'doc';
                    $canonicalMimeType =
                        'application/msword';
                    break;

                case 'docx':
                    if (
                        !in_array(
                            $detectedMimeType,
                            [
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/zip',
                                'application/octet-stream'
                            ],
                            true
                        ) ||
                        !productLoanFileStartsWith(
                            $temporaryPath,
                            'PK'
                        ) ||
                        !productLoanIsValidDocx(
                            $temporaryPath
                        )
                    ) {
                        throw new ProductLoanRequestException(
                            'A DOCX attachment does not contain a valid Word document.'
                        );
                    }

                    $storedExtension = 'docx';

                    $canonicalMimeType =
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;

                default:
                    throw new ProductLoanRequestException(
                        'The attachment type is not allowed.'
                    );
            }

            $fileHash = hash_file(
                'sha256',
                $temporaryPath
            );

            if (
                !is_string($fileHash) ||
                $fileHash === ''
            ) {
                throw new RuntimeException(
                    'Unable to verify an attachment.'
                );
            }

            if (
                isset(
                    $uploadHashes[$fileHash]
                )
            ) {
                throw new ProductLoanRequestException(
                    'This file is already attached: ' .
                    $originalName
                );
            }

            $uploadHashes[$fileHash] = true;

            $validatedFiles[] = [
                'original_name' =>
                    $originalName,

                'tmp_name' =>
                    $temporaryPath,

                'mime_type' =>
                    $canonicalMimeType,

                'extension' =>
                    $storedExtension,

                'file_size' =>
                    $fileSize,

                'file_hash' =>
                    $fileHash
            ];
        }
    } finally {
        finfo_close($fileInfo);
    }

    return $validatedFiles;
}


function productLoanEnsureDirectory(
    string $directory,
    string $errorMessage
): void {
    if (
        !is_dir($directory) &&
        !mkdir(
            $directory,
            0755,
            true
        ) &&
        !is_dir($directory)
    ) {
        throw new RuntimeException(
            $errorMessage
        );
    }
}


function productLoanCleanFolderName(
    string $memberCode
): string {
    $folderName = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        strtoupper(
            trim($memberCode)
        )
    );

    if (
        !is_string($folderName) ||
        $folderName === ''
    ) {
        throw new RuntimeException(
            'The member document folder could not be determined.'
        );
    }

    return $folderName;
}


/* =====================================================
   REQUEST AND SESSION VALIDATION
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {
    productLoanSaveResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}

if (
    strtolower(
        $_SERVER['HTTP_X_REQUESTED_WITH']
        ?? ''
    ) !== 'xmlhttprequest'
) {
    productLoanSaveResponse(
        false,
        'Invalid request.',
        [],
        400
    );
}

$userID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;

if ($userID <= 0) {
    productLoanSaveResponse(
        false,
        'Your session has expired. Please log in again.',
        [],
        401
    );
}

if (
    (int) (
        $_SESSION['lending_access']
        ?? 0
    ) !== 1
) {
    productLoanSaveResponse(
        false,
        'You do not have access to the Lending module.',
        [],
        403
    );
}


/* =====================================================
   CSRF VALIDATION
===================================================== */

$sessionCsrfToken =
    isset($_SESSION['csrf_token'])
        ? (string) $_SESSION['csrf_token']
        : '';

$submittedCsrfToken =
    isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

if (
    $sessionCsrfToken === '' ||
    $submittedCsrfToken === '' ||
    !hash_equals(
        $sessionCsrfToken,
        $submittedCsrfToken
    )
) {
    productLoanSaveResponse(
        false,
        'The security token is invalid or expired. Please refresh the page.',
        [],
        403
    );
}



/* =====================================================
   PRODUCT LOAN FORM VALUES
===================================================== */

$memberID =
    isset($_POST['member_id'])
        ? (int) $_POST['member_id']
        : 0;

$termDays =
    isset($_POST['loan_term'])
        ? (int) $_POST['loan_term']
        : 0;

$loanItemsJSON =
    trim(
        (string) (
            $_POST['loan_items']
            ?? ''
        )
    );

if ($memberID <= 0) {
    productLoanSaveResponse(
        false,
        'Select a registered member.',
        [],
        422
    );
}

$allowedTerms = [
    180 => 10,
    360 => 20
];

if (
    !isset(
        $allowedTerms[$termDays]
    )
) {
    productLoanSaveResponse(
        false,
        'Select a valid loan term.',
        [],
        422
    );
}

$interestRate =
    $allowedTerms[$termDays];

$decodedItems =
    json_decode(
        $loanItemsJSON,
        true
    );

if (
    $loanItemsJSON === '' ||
    json_last_error() !==
        JSON_ERROR_NONE ||
    !is_array($decodedItems) ||
    count($decodedItems) === 0
) {
    productLoanSaveResponse(
        false,
        'Add at least one requested product.',
        [],
        422
    );
}

if (
    count($decodedItems) > 50
) {
    productLoanSaveResponse(
        false,
        'A maximum of 50 products is allowed per application.',
        [],
        422
    );
}


/* =====================================================
   VALIDATE PRODUCTS AND ATTACHMENTS
===================================================== */

try {
    $validatedItems = [];
    $usedProductNames = [];
    $principalCents = 0;

    foreach (
        $decodedItems as $item
    ) {
        if (!is_array($item)) {
            throw new ProductLoanRequestException(
                'A requested product contains invalid data.'
            );
        }

        $productName =
            productLoanNormalizeText(
                $item['product_name']
                ?? ''
            );

        if (
            $productName === '' ||
            productLoanTextLength(
                $productName
            ) < 2
        ) {
            throw new ProductLoanRequestException(
                'Every requested product must have a valid name.'
            );
        }

        if (
            productLoanTextLength(
                $productName
            ) > 180
        ) {
            throw new ProductLoanRequestException(
                'A product name exceeds 180 characters.'
            );
        }

        $productKey =
            function_exists(
                'mb_strtolower'
            )
                ? mb_strtolower(
                    $productName,
                    'UTF-8'
                )
                : strtolower(
                    $productName
                );

        if (
            isset(
                $usedProductNames[
                    $productKey
                ]
            )
        ) {
            throw new ProductLoanRequestException(
                $productName .
                ' was added more than once.'
            );
        }

        $usedProductNames[
            $productKey
        ] = true;

        $quantityRaw =
            $item['quantity']
            ?? null;

        if (
            filter_var(
                $quantityRaw,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new ProductLoanRequestException(
                'Every product quantity must be a whole number.'
            );
        }

        $quantity =
            (int) $quantityRaw;

        if (
            $quantity <= 0 ||
            $quantity > 1000000
        ) {
            throw new ProductLoanRequestException(
                'Every product quantity must be between 1 and 1,000,000.'
            );
        }

        $unitPriceCents =
            productLoanMoneyToCents(
                $item['unit_price']
                ?? null
            );

        $lineTotalCents =
            $quantity *
            $unitPriceCents;

        $principalCents +=
            $lineTotalCents;

        if (
            $principalCents >
            999999999999999
        ) {
            throw new ProductLoanRequestException(
                'The total loan amount exceeds the allowed value.'
            );
        }

        $validatedItems[] = [
            'product_name' =>
                $productName,

            'quantity' =>
                $quantity,

            'unit_price' =>
                productLoanCentsToAmount(
                    $unitPriceCents
                ),

            'line_total' =>
                productLoanCentsToAmount(
                    $lineTotalCents
                )
        ];
    }

    $scheduledInterestCents =
        (int) round(
            $principalCents *
            (
                $interestRate / 100
            )
        );

    $principalAmount =
        productLoanCentsToAmount(
            $principalCents
        );

    $scheduledInterestAmount =
        productLoanCentsToAmount(
            $scheduledInterestCents
        );

    $scheduledTotalAmount =
        productLoanCentsToAmount(
            $principalCents +
            $scheduledInterestCents
        );

    $uploadedFiles =
        productLoanValidateUploads(
            productLoanNormalizeUploads(
                'attachments'
            )
        );
} catch (
    ProductLoanRequestException $exception
) {
    productLoanSaveResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );
} catch (Throwable $exception) {
    error_log(
        'Product Loan validation error: ' .
        $exception->getMessage()
    );

    productLoanSaveResponse(
        false,
        'Unable to validate the Product Loan application.',
        [],
        500
    );
}


/* =====================================================
   DATABASE CONNECTION
===================================================== */

require_once '../../db.php';
require_once '../../includes/history_logger.php';

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    productLoanSaveResponse(
        false,
        'Database connection is unavailable.',
        [],
        500
    );
}

mysqli_set_charset(
    $conn,
    'utf8mb4'
);

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =====================================================
   SAVE TRANSACTION
===================================================== */

$transactionStarted = false;
$movedFilePaths = [];
$createdDirectories = [];

try {
    mysqli_begin_transaction($conn);

    $transactionStarted = true;


    /* Verify logged-in account */

    $userStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                lending_access,
                status

            FROM users

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$userStatement) {
        throw new RuntimeException(
            'Unable to prepare account validation.'
        );
    }

    mysqli_stmt_bind_param(
        $userStatement,
        'i',
        $userID
    );

    mysqli_stmt_execute(
        $userStatement
    );

    $userResult =
        mysqli_stmt_get_result(
            $userStatement
        );

    $user =
        mysqli_fetch_assoc(
            $userResult
        );

    mysqli_stmt_close(
        $userStatement
    );

    if (!$user) {
        throw new ProductLoanRequestException(
            'The logged-in account could not be found.',
            401
        );
    }

    if (
        (int) (
            $user['lending_access']
            ?? 0
        ) !== 1
    ) {
        throw new ProductLoanRequestException(
            'You do not have access to the Lending module.',
            403
        );
    }

    if (
        strtoupper(
            trim(
                (string) (
                    $user['status']
                    ?? ''
                )
            )
        ) !== 'ACTIVE'
    ) {
        throw new ProductLoanRequestException(
            'Your account is inactive.',
            403
        );
    }


    /* Verify member */

    $memberStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id,
                member_code,
                full_name,
                status,
                classification

            FROM members

            WHERE
                member_id = ?
                AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$memberStatement) {
        throw new RuntimeException(
            'Unable to prepare member validation.'
        );
    }

    mysqli_stmt_bind_param(
        $memberStatement,
        'i',
        $memberID
    );

    mysqli_stmt_execute(
        $memberStatement
    );

    $memberResult =
        mysqli_stmt_get_result(
            $memberStatement
        );

    $member =
        mysqli_fetch_assoc(
            $memberResult
        );

    mysqli_stmt_close(
        $memberStatement
    );

    if (!$member) {
        throw new ProductLoanRequestException(
            'The selected member could not be found.',
            404
        );
    }

    if (
        strtoupper(
            trim(
                (string) (
                    $member['status']
                    ?? ''
                )
            )
        ) !== 'ACTIVE'
    ) {
        throw new ProductLoanRequestException(
            'Only active members may apply for a Product Loan.',
            409
        );
    }

    if (
        strtoupper(
            trim(
                (string) (
                    $member['classification']
                    ?? ''
                )
            )
        ) !== 'REGULAR'
    ) {
        throw new ProductLoanRequestException(
            'Only Regular members are eligible to apply for a Product Loan.',
            422
        );
    }


    /* One active Product Loan per member */

    $activeLoanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                product_loan_id,
                status

            FROM product_loans

            WHERE
                member_id = ?

                AND (
                    status = 'Pending'

                    OR

                    (
                        status = 'Delivered'

                        AND (
                            outstanding_principal_amount
                            > 0.009

                            OR

                            unpaid_interest_amount
                            > 0.009
                        )
                    )
                )

            ORDER BY
                product_loan_id DESC

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$activeLoanStatement) {
        throw new RuntimeException(
            'Unable to prepare active-loan validation.'
        );
    }

    mysqli_stmt_bind_param(
        $activeLoanStatement,
        'i',
        $memberID
    );

    mysqli_stmt_execute(
        $activeLoanStatement
    );

    $activeLoanResult =
        mysqli_stmt_get_result(
            $activeLoanStatement
        );

    $activeLoan =
        mysqli_fetch_assoc(
            $activeLoanResult
        );

    mysqli_stmt_close(
        $activeLoanStatement
    );

    if ($activeLoan) {
        throw new ProductLoanRequestException(
            $member['full_name'] .
            ' still has an existing ' .
            strtolower(
                (string)
                $activeLoan['status']
            ) .
            ' Product Loan. It must be completed before another application can be created.',
            409
        );
    }


/* =====================================================
   AUTHORITATIVE PRODUCT LIST VALIDATION

   Product name and unit price are always taken from
   Product List on the server, not trusted from JS.

   Requested quantity represents the number of units
   the member wants to borrow. Product List does not
   maintain an official physical stock quantity.
===================================================== */

    $catalogByIDStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan_product_id,
                product_name,
                loan_price

            FROM product_loan_products

            WHERE
                loan_product_id = ?
                AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


    $catalogByNameStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan_product_id,
                product_name,
                loan_price

            FROM product_loan_products

            WHERE
                archived = 0
                AND LOWER(
                    TRIM(
                        product_name
                    )
                ) =
                LOWER(
                    TRIM(?)
                )

            LIMIT 1

            FOR UPDATE
            "
        );

    if (
        !$catalogByIDStatement ||
        !$catalogByNameStatement
    ) {

        if ($catalogByIDStatement) {
            mysqli_stmt_close(
                $catalogByIDStatement
            );
        }

        if ($catalogByNameStatement) {
            mysqli_stmt_close(
                $catalogByNameStatement
            );
        }

        throw new RuntimeException(
            'Unable to prepare Product List validation.'
        );
    }

    $validatedItems = [];
    $usedCatalogProductIDs = [];
    $principalCents = 0;


    foreach (
        $decodedItems as $item
    ) {

        if (!is_array($item)) {
            throw new ProductLoanRequestException(
                'A requested product contains invalid data.'
            );
        }


        $quantityRaw =
            $item['quantity']
            ?? null;


        if (
            filter_var(
                $quantityRaw,
                FILTER_VALIDATE_INT
            ) === false
        ) {

            throw new ProductLoanRequestException(
                'Every product quantity must be a whole number.'
            );
        }


        $requestedQuantity =
            (int) $quantityRaw;


        if (
            $requestedQuantity <= 0 ||
            $requestedQuantity > 1000000
        ) {

            throw new ProductLoanRequestException(
                'Every product quantity must be between 1 and 1,000,000.'
            );
        }


        $submittedProductID =
            filter_var(
                $item['loan_product_id']
                ?? null,
                FILTER_VALIDATE_INT
            );


        $submittedProductName =
            productLoanNormalizeText(
                $item['product_name']
                ?? ''
            );


        $catalogProduct =
            null;


        if (
            $submittedProductID !== false &&
            $submittedProductID !== null &&
            $submittedProductID > 0
        ) {

            mysqli_stmt_bind_param(
                $catalogByIDStatement,
                'i',
                $submittedProductID
            );


            if (
                !mysqli_stmt_execute(
                    $catalogByIDStatement
                )
            ) {

                throw new RuntimeException(
                    'Unable to validate a selected Product List item.'
                );
            }


            $catalogResult =
                mysqli_stmt_get_result(
                    $catalogByIDStatement
                );


            $catalogProduct =
                $catalogResult
                    ? mysqli_fetch_assoc(
                        $catalogResult
                    )
                    : null;
        }


        if (
            !$catalogProduct &&
            $submittedProductName !== ''
        ) {

            mysqli_stmt_bind_param(
                $catalogByNameStatement,
                's',
                $submittedProductName
            );


            if (
                !mysqli_stmt_execute(
                    $catalogByNameStatement
                )
            ) {

                throw new RuntimeException(
                    'Unable to validate a requested product.'
                );
            }


            $catalogResult =
                mysqli_stmt_get_result(
                    $catalogByNameStatement
                );


            $catalogProduct =
                $catalogResult
                    ? mysqli_fetch_assoc(
                        $catalogResult
                    )
                    : null;
        }


        if (!$catalogProduct) {

            throw new ProductLoanRequestException(
                $submittedProductName !== ''
                    ? (
                        $submittedProductName .
                        ' is no longer available in Product List.'
                    )
                    : 'A selected Product List item could not be found.',
                409
            );
        }


        $catalogProductID =
            (int) (
                $catalogProduct[
                    'loan_product_id'
                ]
                ?? 0
            );


        $catalogProductName =
            productLoanNormalizeText(
                $catalogProduct[
                    'product_name'
                ]
                ?? ''
            );


        if (
            $catalogProductID <= 0 ||
            $catalogProductName === ''
        ) {

            throw new RuntimeException(
                'A Product List item contains invalid data.'
            );
        }


        if (
            isset(
                $usedCatalogProductIDs[
                    $catalogProductID
                ]
            )
        ) {

            throw new ProductLoanRequestException(
                $catalogProductName .
                ' was added more than once.'
            );
        }


        $usedCatalogProductIDs[
            $catalogProductID
        ] = true;

        $unitPriceCents =
            productLoanMoneyToCents(
                $catalogProduct[
                    'loan_price'
                ]
                ?? null
            );


        $lineTotalCents =
            $requestedQuantity *
            $unitPriceCents;


        $principalCents +=
            $lineTotalCents;


        if (
            $principalCents >
            999999999999999
        ) {

            throw new ProductLoanRequestException(
                'The total loan amount exceeds the allowed value.'
            );
        }


        $validatedItems[] = [
            'loan_product_id' =>
                $catalogProductID,

            'product_name' =>
                $catalogProductName,

            'quantity' =>
                $requestedQuantity,

            'unit_price' =>
                productLoanCentsToAmount(
                    $unitPriceCents
                ),

            'line_total' =>
                productLoanCentsToAmount(
                    $lineTotalCents
                )
        ];
    }


    mysqli_stmt_close(
        $catalogByIDStatement
    );

    mysqli_stmt_close(
        $catalogByNameStatement
    );

    $scheduledInterestCents =
        (int) round(
            $principalCents *
            (
                $interestRate / 100
            )
        );


    $principalAmount =
        productLoanCentsToAmount(
            $principalCents
        );


    $scheduledInterestAmount =
        productLoanCentsToAmount(
            $scheduledInterestCents
        );


    $scheduledTotalAmount =
        productLoanCentsToAmount(
            $principalCents +
            $scheduledInterestCents
        );


    $applicationDate =
        date('Y-m-d');


    /* Insert Product Loan */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO product_loans (
                member_id,
                application_date,
                interest_rate,
                term_days,
                principal_amount,
                scheduled_interest_amount,
                scheduled_total_amount,
                outstanding_principal_amount,
                status,
                created_by
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'Pending',
                ?
            )
            "
        );

    if (!$loanStatement) {
        throw new RuntimeException(
            'Unable to prepare the Product Loan record.'
        );
    }

    mysqli_stmt_bind_param(
        $loanStatement,
        'isdiddddi',
        $memberID,
        $applicationDate,
        $interestRate,
        $termDays,
        $principalAmount,
        $scheduledInterestAmount,
        $scheduledTotalAmount,
        $principalAmount,
        $userID
    );

    if (
        !mysqli_stmt_execute(
            $loanStatement
        )
    ) {
        throw new RuntimeException(
            'Unable to create the Product Loan record.'
        );
    }

    $productLoanID =
        (int) mysqli_insert_id(
            $conn
        );

    mysqli_stmt_close(
        $loanStatement
    );


    /* Insert requested products */

    $itemStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO product_loan_items (
                product_loan_id,
                product_name,
                quantity,
                unit_price,
                line_total
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?
            )
            "
        );

    if (!$itemStatement) {
        throw new RuntimeException(
            'Unable to prepare requested products.'
        );
    }

    foreach (
        $validatedItems as $item
    ) {
        $productName =
            $item['product_name'];

        $quantity =
            $item['quantity'];

        $unitPrice =
            $item['unit_price'];

        $lineTotal =
            $item['line_total'];

        mysqli_stmt_bind_param(
            $itemStatement,
            'isidd',
            $productLoanID,
            $productName,
            $quantity,
            $unitPrice,
            $lineTotal
        );

        if (
            !mysqli_stmt_execute(
                $itemStatement
            )
        ) {
            throw new RuntimeException(
                'Unable to save a requested product.'
            );
        }
    }

    mysqli_stmt_close(
        $itemStatement
    );


    /* Save attachments */

    if (count($uploadedFiles) > 0) {
        $systemRoot =
            dirname(__DIR__, 2);

        $attachmentBaseDirectory =
            $systemRoot .
            DIRECTORY_SEPARATOR .
            'assets' .
            DIRECTORY_SEPARATOR .
            'documents' .
            DIRECTORY_SEPARATOR .
            'lending' .
            DIRECTORY_SEPARATOR .
            'product_loan';

        productLoanEnsureDirectory(
            $attachmentBaseDirectory,
            'Unable to create the Product Loan document folder.'
        );

        $securityFilePath =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            '.htaccess';

        if (!is_file($securityFilePath)) {
            $securityRules =
                "Options -Indexes\n" .
                "Require all denied\n";

            if (
                file_put_contents(
                    $securityFilePath,
                    $securityRules,
                    LOCK_EX
                ) === false
            ) {
                throw new RuntimeException(
                    'Unable to secure the Product Loan document folder.'
                );
            }
        }

        $memberFolderName =
            productLoanCleanFolderName(
                (string)
                $member['member_code']
            );

        $loanFolderName =
            'PL-' .
            str_pad(
                (string) $productLoanID,
                6,
                '0',
                STR_PAD_LEFT
            );

        $memberDirectory =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            $memberFolderName;

        $memberDirectoryExisted =
            is_dir($memberDirectory);

        productLoanEnsureDirectory(
            $memberDirectory,
            "Unable to create the member's Product Loan document folder."
        );

        if (!$memberDirectoryExisted) {
            $createdDirectories[] =
                $memberDirectory;
        }

        $attachmentDirectory =
            $memberDirectory .
            DIRECTORY_SEPARATOR .
            $loanFolderName;

        $attachmentDirectoryExisted =
            is_dir($attachmentDirectory);

        productLoanEnsureDirectory(
            $attachmentDirectory,
            'Unable to create the attachment folder.'
        );

        if (!$attachmentDirectoryExisted) {
            $createdDirectories[] =
                $attachmentDirectory;
        }

        $attachmentStatement =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO product_loan_attachments (
                    product_loan_id,
                    original_file_name,
                    stored_file_name,
                    file_path,
                    mime_type,
                    file_size,
                    file_hash,
                    uploaded_by
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                "
            );

        if (!$attachmentStatement) {
            throw new RuntimeException(
                'Unable to prepare attachments.'
            );
        }

        $reservedStoredFileNames = [];

        foreach (
            $uploadedFiles as $file
        ) {
            $storedFileName =
                productLoanCreateReadableStoredFileName(
                    $file['original_name'],
                    $file['extension'],
                    $attachmentDirectory,
                    $reservedStoredFileNames
                );

            $absoluteFilePath =
                $attachmentDirectory .
                DIRECTORY_SEPARATOR .
                $storedFileName;

            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $absoluteFilePath
                )
            ) {
                throw new RuntimeException(
                    'Unable to store an attachment.'
                );
            }

            $movedFilePaths[] =
                $absoluteFilePath;

            $storedHash =
                hash_file(
                    'sha256',
                    $absoluteFilePath
                );

            if (
                !is_string($storedHash) ||
                !hash_equals(
                    $file['file_hash'],
                    $storedHash
                )
            ) {
                throw new RuntimeException(
                    'Unable to verify an attachment after storage.'
                );
            }

            $relativeFilePath =
                'assets/documents/lending/product_loan/' .
                $memberFolderName .
                '/' .
                $loanFolderName .
                '/' .
                $storedFileName;

            $originalFileName =
                $file['original_name'];

            $mimeType =
                $file['mime_type'];

            $fileSize =
                $file['file_size'];

            $fileHash =
                $storedHash;

            mysqli_stmt_bind_param(
                $attachmentStatement,
                'issssisi',
                $productLoanID,
                $originalFileName,
                $storedFileName,
                $relativeFilePath,
                $mimeType,
                $fileSize,
                $fileHash,
                $userID
            );

            if (
                !mysqli_stmt_execute(
                    $attachmentStatement
                )
            ) {
                throw new RuntimeException(
                    'Unable to record an attachment.'
                );
            }
        }

        mysqli_stmt_close(
            $attachmentStatement
        );
    }


    /* Initial Pending status history */

    $statusReason =
        'Product Loan application created.';

    $statusStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO product_loan_status_history (
                product_loan_id,
                previous_status,
                new_status,
                changed_by,
                reason
            ) VALUES (
                ?,
                NULL,
                'Pending',
                ?,
                ?
            )
            "
        );

    if (!$statusStatement) {
        throw new RuntimeException(
            'Unable to prepare the initial status history.'
        );
    }

    mysqli_stmt_bind_param(
        $statusStatement,
        'iis',
        $productLoanID,
        $userID,
        $statusReason
    );

    if (
        !mysqli_stmt_execute(
            $statusStatement
        )
    ) {
        throw new RuntimeException(
            'Unable to save the initial status history.'
        );
    }

    mysqli_stmt_close(
        $statusStatement
    );


    /* =====================================================
    SAVE PRODUCT LOAN HISTORY LOG
    ===================================================== */

    $loanReference =
        'PL-' .
        str_pad(
            (string) $productLoanID,
            6,
            '0',
            STR_PAD_LEFT
        );

    $productHistoryParts = [];

    foreach ($validatedItems as $validatedItem) {
        $productHistoryParts[] =
            $validatedItem['product_name'] .
            ' × ' .
            number_format(
                (int) $validatedItem['quantity']
            ) .
            ' at ₱' .
            number_format(
                (float) $validatedItem['unit_price'],
                2
            );
    }

    $productHistoryText =
        implode(
            ', ',
            $productHistoryParts
        );

    $attachmentCount =
        count(
            $uploadedFiles
        );

    $historyMessage =
        'added Product Loan "' .
        $loanReference .
        '" for member "' .
        $member['full_name'] .
        '" (' .
        $member['member_code'] .
        '): ' .
        $productHistoryText .
        '. Principal ₱' .
        number_format(
            $principalAmount,
            2
        ) .
        ', interest ' .
        number_format(
            $interestRate,
            0
        ) .
        '% for ' .
        number_format(
            $termDays
        ) .
        ' days, scheduled total ₱' .
        number_format(
            $scheduledTotalAmount,
            2
        ) .
        ', status Pending, with ' .
        number_format(
            $attachmentCount
        ) .
        (
            $attachmentCount === 1
                ? ' attachment.'
                : ' attachments.'
        );

    log_activity(
        $conn,
        'Lending',
        'Added',
        $historyMessage,
        $productLoanID
    );


    /* =====================================================
    COMMIT DATABASE CHANGES
    ===================================================== */

    mysqli_commit($conn);

    $transactionStarted = false;

    productLoanSaveResponse(
        true,
        'The Product Loan application for ' .
        $member['full_name'] .
        ' was saved as Pending.',
        [
            'product_loan_id' =>
                $productLoanID,

            'status' =>
                'Pending',

            'application_date' =>
                $applicationDate,

            'principal_amount' =>
                number_format(
                    $principalAmount,
                    2,
                    '.',
                    ''
                ),

            'scheduled_interest_amount' =>
                number_format(
                    $scheduledInterestAmount,
                    2,
                    '.',
                    ''
                ),

            'scheduled_total_amount' =>
                number_format(
                    $scheduledTotalAmount,
                    2,
                    '.',
                    ''
                )
        ],
        201
    );
} catch (
    ProductLoanRequestException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback($conn);
    }

    foreach (
        $movedFilePaths as $movedFilePath
    ) {
        if (is_file($movedFilePath)) {
            @unlink($movedFilePath);
        }
    }

    foreach (
        array_reverse(
            $createdDirectories
        ) as $directory
    ) {
        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }

    productLoanSaveResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );
} catch (Throwable $exception) {
    if ($transactionStarted) {
        mysqli_rollback($conn);
    }

    foreach (
        $movedFilePaths as $movedFilePath
    ) {
        if (is_file($movedFilePath)) {
            @unlink($movedFilePath);
        }
    }

    foreach (
        array_reverse(
            $createdDirectories
        ) as $directory
    ) {
        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }

    error_log(
        'Save Product Loan error: ' .
        $exception->getMessage()
    );

    productLoanSaveResponse(
        false,
        'Unable to save the Product Loan application. Please try again.',
        [],
        500
    );
}