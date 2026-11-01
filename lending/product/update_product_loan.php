<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");
ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


class ProductLoanUpdateException extends RuntimeException
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


function updateProductLoanResponse(
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

    exit;
}


function updateProductLoanNormalizeText($value): string
{
    $value = trim((string) $value);

    $normalized = preg_replace(
        "/\s+/u",
        " ",
        $value
    );

    return $normalized !== null
        ? $normalized
        : $value;
}


function updateProductLoanTextLength(string $value): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}


function updateProductLoanMoneyToCents($value): int
{
    if (!is_numeric($value)) {
        throw new ProductLoanUpdateException(
            "A product contains an invalid price."
        );
    }

    $number = (float) $value;

    if (
        !is_finite($number) ||
        $number <= 0
    ) {
        throw new ProductLoanUpdateException(
            "Product prices must be greater than zero."
        );
    }

    if ($number > 9999999999.99) {
        throw new ProductLoanUpdateException(
            "A product price exceeds the allowed amount."
        );
    }

    return (int) round(
        $number * 100
    );
}


function updateProductLoanCentsToAmount(int $cents): float
{
    return round(
        $cents / 100,
        2
    );
}


function updateProductLoanCleanFileName(string $fileName): string
{
    $fileName = basename($fileName);

    $fileName = preg_replace(
        '/[\x00-\x1F\x7F]/u',
        "",
        $fileName
    );

    $fileName = trim(
        (string) $fileName
    );

    if ($fileName === "") {
        $fileName = "attachment";
    }

    return function_exists("mb_substr")
        ? mb_substr(
            $fileName,
            0,
            240,
            "UTF-8"
        )
        : substr(
            $fileName,
            0,
            240
        );
}


function updateProductLoanSanitizeStoredBaseName(
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

    $reservedWindowsNames = [
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
            $reservedWindowsNames,
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


function updateProductLoanCreateReadableStoredFileName(
    string $originalFileName,
    string $extension,
    string $directory,
    array &$reservedFileNames
): string {
    $safeBaseName =
        updateProductLoanSanitizeStoredBaseName(
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


function updateProductLoanFileExtension(string $fileName): string
{
    return strtolower(
        (string) pathinfo(
            basename($fileName),
            PATHINFO_EXTENSION
        )
    );
}


function updateProductLoanHasDangerousEmbeddedExtension(
    string $fileName
): bool {
    return preg_match(
        '/\.(php[0-9]*|phtml|phar|cgi|pl|py|sh|exe|com|bat|cmd|msi|js|html?|svg|sql)(?:\.|$)/i',
        basename($fileName)
    ) === 1;
}


function updateProductLoanFileStartsWith(
    string $filePath,
    string $expectedBytes
): bool {
    $handle = @fopen(
        $filePath,
        "rb"
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


function updateProductLoanIsValidLegacyDoc(string $filePath): bool
{
    if (
        !updateProductLoanFileStartsWith(
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

    $wordDocumentStreamName =
        "W\x00o\x00r\x00d\x00" .
        "D\x00o\x00c\x00u\x00m\x00e\x00n\x00t\x00";

    return strpos(
        $contents,
        $wordDocumentStreamName
    ) !== false;
}


function updateProductLoanIsValidDocx(string $filePath): bool
{
    if (!class_exists("ZipArchive")) {
        throw new RuntimeException(
            "The PHP ZIP extension is unavailable. Enable extension=zip in php.ini."
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
            "[Content_Types].xml",
            "_rels/.rels",
            "word/document.xml"
        ];

        foreach ($requiredEntries as $entryName) {
            if (
                $archive->locateName(
                    $entryName
                ) === false
            ) {
                return false;
            }
        }

        $contentTypesStat =
            $archive->statName(
                "[Content_Types].xml"
            );

        if (
            !is_array($contentTypesStat) ||
            (int) (
                $contentTypesStat["size"] ?? 0
            ) <= 0 ||
            (int) (
                $contentTypesStat["size"] ?? 0
            ) > 1024 * 1024
        ) {
            return false;
        }

        $contentTypes =
            $archive->getFromName(
                "[Content_Types].xml"
            );

        return is_string($contentTypes) &&
            stripos(
                $contentTypes,
                "wordprocessingml.document.main+xml"
            ) !== false;
    } finally {
        $archive->close();
    }
}


function updateProductLoanCleanFolderName(string $memberCode): string
{
    $folderName = preg_replace(
        '/[^A-Za-z0-9_-]/',
        "",
        strtoupper(
            trim($memberCode)
        )
    );

    if (
        !is_string($folderName) ||
        $folderName === ""
    ) {
        throw new RuntimeException(
            "The member document folder could not be determined."
        );
    }

    return $folderName;
}


function updateProductLoanEnsureDirectory(
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


function updateProductLoanNormalizeUploads(string $field): array
{
    if (
        !isset($_FILES[$field]) ||
        !isset($_FILES[$field]["name"])
    ) {
        return [];
    }

    $files = $_FILES[$field];

    $normalized = [];

    if (is_array($files["name"])) {
        $count = count(
            $files["name"]
        );

        for (
            $index = 0;
            $index < $count;
            $index++
        ) {
            $error = isset(
                $files["error"][$index]
            )
                ? (int) $files["error"][$index]
                : UPLOAD_ERR_NO_FILE;

            if (
                $error ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $normalized[] = [
                "name" =>
                    $files["name"][$index] ?? "",

                "tmp_name" =>
                    $files["tmp_name"][$index] ?? "",

                "error" =>
                    $error,

                "size" =>
                    isset($files["size"][$index])
                        ? (int) $files["size"][$index]
                        : 0
            ];
        }

        return $normalized;
    }

    $error = isset($files["error"])
        ? (int) $files["error"]
        : UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_NO_FILE) {
        $normalized[] = [
            "name" =>
                $files["name"] ?? "",

            "tmp_name" =>
                $files["tmp_name"] ?? "",

            "error" =>
                $error,

            "size" =>
                isset($files["size"])
                    ? (int) $files["size"]
                    : 0
        ];
    }

    return $normalized;
}


function updateProductLoanUploadError(int $error): string
{
    switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return
                "An attachment exceeds the server upload limit.";

        case UPLOAD_ERR_PARTIAL:
            return
                "An attachment was only partially uploaded.";

        case UPLOAD_ERR_NO_TMP_DIR:
            return
                "The temporary upload folder is unavailable.";

        case UPLOAD_ERR_CANT_WRITE:
            return
                "The server could not write an attachment.";

        case UPLOAD_ERR_EXTENSION:
            return
                "A server extension stopped an attachment upload.";

        default:
            return
                "An attachment could not be uploaded.";
    }
}


function updateProductLoanValidateUploads(array $files): array
{
    $maximumAttachments = 10;

    $maximumFileSize =
        10 * 1024 * 1024;

    $maximumTotalUploadSize =
        50 * 1024 * 1024;

    $allowedOriginalExtensions = [
        "jpg",
        "jpeg",
        "png",
        "pdf",
        "doc",
        "docx"
    ];

    if (
        count($files) >
        $maximumAttachments
    ) {
        throw new ProductLoanUpdateException(
            "A maximum of 10 attachments is allowed per upload."
        );
    }

    if (count($files) === 0) {
        return [];
    }

    if (!function_exists("finfo_open")) {
        throw new RuntimeException(
            "The PHP Fileinfo extension is unavailable."
        );
    }

    $fileInfo = finfo_open(
        FILEINFO_MIME_TYPE
    );

    if (!$fileInfo) {
        throw new RuntimeException(
            "Unable to inspect attachments."
        );
    }

    $validated = [];

    $totalUploadSize = 0;

    try {
        foreach ($files as $file) {
            $uploadError = (int) (
                $file["error"] ??
                UPLOAD_ERR_NO_FILE
            );

            if (
                $uploadError !==
                UPLOAD_ERR_OK
            ) {
                throw new ProductLoanUpdateException(
                    updateProductLoanUploadError(
                        $uploadError
                    )
                );
            }

            $fileSize = (int) (
                $file["size"] ?? 0
            );

            if ($fileSize <= 0) {
                throw new ProductLoanUpdateException(
                    "An attachment is empty."
                );
            }

            if (
                $fileSize >
                $maximumFileSize
            ) {
                throw new ProductLoanUpdateException(
                    "Each attachment must not exceed 10 MB."
                );
            }

            $totalUploadSize +=
                $fileSize;

            if (
                $totalUploadSize >
                $maximumTotalUploadSize
            ) {
                throw new ProductLoanUpdateException(
                    "The combined attachment size must not exceed 50 MB per upload."
                );
            }

            $temporaryPath = (string) (
                $file["tmp_name"] ?? ""
            );

            $originalName = (string) (
                $file["name"] ?? ""
            );

            $originalExtension =
                updateProductLoanFileExtension(
                    $originalName
                );

            if (
                !in_array(
                    $originalExtension,
                    $allowedOriginalExtensions,
                    true
                )
            ) {
                throw new ProductLoanUpdateException(
                    "Only JPG, PNG, PDF, DOC, and DOCX attachments are allowed."
                );
            }

            if (
                updateProductLoanHasDangerousEmbeddedExtension(
                    $originalName
                )
            ) {
                throw new ProductLoanUpdateException(
                    "An unsafe attachment filename was detected."
                );
            }

            if (
                !is_uploaded_file(
                    $temporaryPath
                )
            ) {
                throw new ProductLoanUpdateException(
                    "An invalid attachment upload was detected.",
                    400
                );
            }

            $mimeType = finfo_file(
                $fileInfo,
                $temporaryPath
            );

            if (!is_string($mimeType)) {
                throw new ProductLoanUpdateException(
                    "Unable to identify an attachment's real file type."
                );
            }

            $storedExtension = "";

            $canonicalMimeType = "";

            switch ($originalExtension) {
                case "jpg":
                case "jpeg":
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $mimeType !== "image/jpeg" ||
                        !is_array($imageDetails) ||
                        (
                            $imageDetails["mime"] ?? ""
                        ) !== "image/jpeg"
                    ) {
                        throw new ProductLoanUpdateException(
                            "A JPG attachment does not contain a valid JPG image."
                        );
                    }

                    $storedExtension =
                        "jpg";

                    $canonicalMimeType =
                        "image/jpeg";

                    break;

                case "png":
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $mimeType !== "image/png" ||
                        !is_array($imageDetails) ||
                        (
                            $imageDetails["mime"] ?? ""
                        ) !== "image/png"
                    ) {
                        throw new ProductLoanUpdateException(
                            "A PNG attachment does not contain a valid PNG image."
                        );
                    }

                    $storedExtension =
                        "png";

                    $canonicalMimeType =
                        "image/png";

                    break;

                case "pdf":
                    if (
                        !in_array(
                            $mimeType,
                            [
                                "application/pdf",
                                "application/x-pdf"
                            ],
                            true
                        ) ||
                        !updateProductLoanFileStartsWith(
                            $temporaryPath,
                            "%PDF-"
                        )
                    ) {
                        throw new ProductLoanUpdateException(
                            "A PDF attachment does not contain a valid PDF document."
                        );
                    }

                    $storedExtension =
                        "pdf";

                    $canonicalMimeType =
                        "application/pdf";

                    break;

                case "doc":
                    $validDocMimeTypes = [
                        "application/msword",
                        "application/x-ole-storage",
                        "application/CDFV2",
                        "application/octet-stream"
                    ];

                    if (
                        !in_array(
                            $mimeType,
                            $validDocMimeTypes,
                            true
                        ) ||
                        !updateProductLoanIsValidLegacyDoc(
                            $temporaryPath
                        )
                    ) {
                        throw new ProductLoanUpdateException(
                            "A DOC attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension =
                        "doc";

                    $canonicalMimeType =
                        "application/msword";

                    break;

                case "docx":
                    $validDocxMimeTypes = [
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "application/zip",
                        "application/octet-stream"
                    ];

                    if (
                        !in_array(
                            $mimeType,
                            $validDocxMimeTypes,
                            true
                        ) ||
                        !updateProductLoanFileStartsWith(
                            $temporaryPath,
                            "PK"
                        ) ||
                        !updateProductLoanIsValidDocx(
                            $temporaryPath
                        )
                    ) {
                        throw new ProductLoanUpdateException(
                            "A DOCX attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension =
                        "docx";

                    $canonicalMimeType =
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

                    break;

                default:
                    throw new ProductLoanUpdateException(
                        "The attachment type is not allowed."
                    );
            }

            $validated[] = [
                "original_name" =>
                    updateProductLoanCleanFileName(
                        $originalName
                    ),

                "tmp_name" =>
                    $temporaryPath,

                "mime_type" =>
                    $canonicalMimeType,

                "extension" =>
                    $storedExtension,

                "file_size" =>
                    $fileSize
            ];
        }
    } finally {
        finfo_close(
            $fileInfo
        );
    }

    return $validated;
}


/* =========================================================
   REQUEST AND SESSION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {
    updateProductLoanResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}

$requestedWith =
    $_SERVER["HTTP_X_REQUESTED_WITH"] ?? "";

if (
    strtolower($requestedWith) !==
    "xmlhttprequest"
) {
    updateProductLoanResponse(
        false,
        "Invalid request.",
        [],
        400
    );
}

$userID = isset($_SESSION["user_id"])
    ? (int) $_SESSION["user_id"]
    : 0;

if ($userID <= 0) {
    updateProductLoanResponse(
        false,
        "Your session has expired. Please log in again.",
        [],
        401
    );
}

if (
    (int) (
        $_SESSION["lending_access"]
        ?? 0
    ) !== 1
) {
    updateProductLoanResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}

$sessionCsrfToken =
    (string) (
        $_SESSION["csrf_token"]
        ?? ""
    );

$submittedCsrfToken =
    (string) (
        $_POST["csrf_token"]
        ?? ""
    );

if (
    $sessionCsrfToken === "" ||
    $submittedCsrfToken === "" ||
    !hash_equals(
        $sessionCsrfToken,
        $submittedCsrfToken
    )
) {
    updateProductLoanResponse(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        [],
        419
    );
}


/* =========================================================
   POST VALUES
========================================================= */

$productLoanID =
    isset($_POST["product_loan_id"])
        ? (int) $_POST["product_loan_id"]
        : 0;

$memberID =
    isset($_POST["member_id"])
        ? (int) $_POST["member_id"]
        : 0;

$termDays =
    isset($_POST["loan_term"])
        ? (int) $_POST["loan_term"]
        : 0;

$newStatus = trim(
    (string) (
        $_POST["status"] ?? ""
    )
);

$rejectionReason =
    updateProductLoanNormalizeText(
        $_POST["rejection_reason"] ?? ""
    );

$itemsJSON = trim(
    (string) (
        $_POST["loan_items"] ?? ""
    )
);

$removedAttachmentsJSON = trim(
    (string) (
        $_POST["removed_attachment_ids"] ?? "[]"
    )
);

if ($productLoanID <= 0) {
    updateProductLoanResponse(
        false,
        "Invalid Product Loan ID.",
        [],
        422
    );
}

if ($memberID <= 0) {
    updateProductLoanResponse(
        false,
        "Select a registered member.",
        [],
        422
    );
}

$termOptions = [
    180 => 10,
    360 => 20
];

if (!isset($termOptions[$termDays])) {
    updateProductLoanResponse(
        false,
        "Select a valid loan term.",
        [],
        422
    );
}

$interestRate =
    $termOptions[$termDays];

$allowedStatuses = [
    "Pending",
    "Delivered",
    "Rejected"
];

if (
    !in_array(
        $newStatus,
        $allowedStatuses,
        true
    )
) {
    updateProductLoanResponse(
        false,
        "Invalid Product Loan status.",
        [],
        422
    );
}

if (
    $newStatus === "Rejected" &&
    $rejectionReason === ""
) {
    updateProductLoanResponse(
        false,
        "Enter the rejection reason.",
        [],
        422
    );
}


/* =========================================================
   ITEMS AND REMOVED ATTACHMENTS
========================================================= */

$decodedItems = json_decode(
    $itemsJSON,
    true
);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($decodedItems) ||
    count($decodedItems) === 0
) {
    updateProductLoanResponse(
        false,
        "Add at least one requested product.",
        [],
        422
    );
}

if (count($decodedItems) > 50) {
    updateProductLoanResponse(
        false,
        "A maximum of 50 products is allowed.",
        [],
        422
    );
}

$removedAttachmentIDs =
    json_decode(
        $removedAttachmentsJSON,
        true
    );

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($removedAttachmentIDs)
) {
    updateProductLoanResponse(
        false,
        "Invalid removed attachment data.",
        [],
        422
    );
}

$removedAttachmentIDs = array_values(
    array_unique(
        array_filter(
            array_map(
                "intval",
                $removedAttachmentIDs
            ),
            function ($attachmentID) {
                return $attachmentID > 0;
            }
        )
    )
);


/* =========================================================
   RECALCULATE VALUES
========================================================= */

try {
    $validatedItems = [];

    $usedNames = [];

    $principalCents = 0;

    foreach ($decodedItems as $item) {
        if (!is_array($item)) {
            throw new ProductLoanUpdateException(
                "A requested product contains invalid data."
            );
        }

        $productName =
            updateProductLoanNormalizeText(
                $item["product_name"] ?? ""
            );

        if (
            $productName === "" ||
            updateProductLoanTextLength(
                $productName
            ) < 2 ||
            updateProductLoanTextLength(
                $productName
            ) > 180
        ) {
            throw new ProductLoanUpdateException(
                "Every requested product must have a valid name."
            );
        }

        $productKey =
            function_exists("mb_strtolower")
                ? mb_strtolower(
                    $productName,
                    "UTF-8"
                )
                : strtolower(
                    $productName
                );

        if (isset($usedNames[$productKey])) {
            throw new ProductLoanUpdateException(
                $productName .
                " was added more than once."
            );
        }

        $usedNames[$productKey] =
            true;

        $quantityRaw =
            $item["quantity"] ?? null;

        if (
            filter_var(
                $quantityRaw,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new ProductLoanUpdateException(
                "Every quantity must be a whole number."
            );
        }

        $quantity =
            (int) $quantityRaw;

        if (
            $quantity <= 0 ||
            $quantity > 1000000
        ) {
            throw new ProductLoanUpdateException(
                "Every quantity must be between 1 and 1,000,000."
            );
        }

        $unitPriceCents =
            updateProductLoanMoneyToCents(
                $item["unit_price"] ?? null
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
            throw new ProductLoanUpdateException(
                "The total loan amount exceeds the allowed value."
            );
        }

        $validatedItems[] = [
            "product_name" =>
                $productName,

            "quantity" =>
                $quantity,

            "unit_price" =>
                updateProductLoanCentsToAmount(
                    $unitPriceCents
                ),

            "line_total" =>
                updateProductLoanCentsToAmount(
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

    $scheduledTotalCents =
        $principalCents +
        $scheduledInterestCents;

    $principalAmount =
        updateProductLoanCentsToAmount(
            $principalCents
        );

    $scheduledInterestAmount =
        updateProductLoanCentsToAmount(
            $scheduledInterestCents
        );

    $scheduledTotalAmount =
        updateProductLoanCentsToAmount(
            $scheduledTotalCents
        );

    $newUploads =
        updateProductLoanValidateUploads(
            updateProductLoanNormalizeUploads(
                "attachments"
            )
        );
} catch (
    ProductLoanUpdateException $exception
) {
    updateProductLoanResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );
} catch (Throwable $exception) {
    error_log(
        "Update Product Loan validation error: " .
        $exception->getMessage()
    );

    updateProductLoanResponse(
        false,
        "Unable to validate the Product Loan update.",
        [],
        500
    );
}


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";
require_once "../../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    updateProductLoanResponse(
        false,
        "Database connection is unavailable.",
        [],
        500
    );
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);

$transactionStarted = false;

$movedFiles = [];

$createdDirectories = [];


/* =========================================================
   UPDATE TRANSACTION
========================================================= */

try {
    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* ACCOUNT */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT lending_permission
            FROM users
            WHERE user_id = ?
            LIMIT 1
            FOR UPDATE
            "
        );

    if (!$accountStatement) {
        throw new RuntimeException(
            "Unable to prepare account validation."
        );
    }

    mysqli_stmt_bind_param(
        $accountStatement,
        "i",
        $userID
    );

    mysqli_stmt_execute(
        $accountStatement
    );

    $account =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $accountStatement
            )
        );

    mysqli_stmt_close(
        $accountStatement
    );

    if (!$account) {
        throw new ProductLoanUpdateException(
            "The logged-in account could not be found.",
            401
        );
    }

    $hasFullAccess =
        strtolower(
            trim(
                (string) (
                    $account["lending_permission"] ?? ""
                )
            )
        ) === "full";


    /* LOCK PRODUCT LOAN */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                status,
                member_id
            FROM product_loans
            WHERE
                product_loan_id = ?
                AND is_archived = 0
            LIMIT 1
            FOR UPDATE
            "
        );

    if (!$loanStatement) {
        throw new RuntimeException(
            "Unable to prepare Product Loan validation."
        );
    }

    mysqli_stmt_bind_param(
        $loanStatement,
        "i",
        $productLoanID
    );

    mysqli_stmt_execute(
        $loanStatement
    );

    $existingLoan =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $loanStatement
            )
        );

    mysqli_stmt_close(
        $loanStatement
    );

    if (!$existingLoan) {
        throw new ProductLoanUpdateException(
            "The Product Loan record could not be found.",
            404
        );
    }

    if (
        $existingLoan["status"] !==
        "Pending"
    ) {
        throw new ProductLoanUpdateException(
            "Delivered or Rejected Product Loans can no longer be edited.",
            409
        );
    }

    if (
        !$hasFullAccess &&
        $newStatus !== "Pending"
    ) {
        throw new ProductLoanUpdateException(
            "Your Lending Permission is limited. The status must remain Pending.",
            403
        );
    }


    /* MEMBER */

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
            "Unable to prepare member validation."
        );
    }

    mysqli_stmt_bind_param(
        $memberStatement,
        "i",
        $memberID
    );

    mysqli_stmt_execute(
        $memberStatement
    );

    $member =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $memberStatement
            )
        );

    mysqli_stmt_close(
        $memberStatement
    );

    if (!$member) {
        throw new ProductLoanUpdateException(
            "The selected member could not be found.",
            404
        );
    }

    if (
        strtoupper(
            trim(
                (string) (
                    $member["status"] ?? ""
                )
            )
        ) !== "ACTIVE"
    ) {
        throw new ProductLoanUpdateException(
            "Only active members may apply for a Product Loan.",
            409
        );
    }

    if (
        strtoupper(
            trim(
                (string) (
                    $member["classification"] ?? ""
                )
            )
        ) !== "REGULAR"
    ) {
        throw new ProductLoanUpdateException(
            "Only Regular members are eligible to apply for a Product Loan.",
            422
        );
    }


    /* OTHER ACTIVE LOAN CHECK */

    $activeStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT product_loan_id
            FROM product_loans
            WHERE
                member_id = ?
                AND product_loan_id <> ?
                AND (
                    status = 'Pending'
                    OR (
                        status = 'Delivered'
                        AND (
                            outstanding_principal_amount > 0.009
                            OR unpaid_interest_amount > 0.009
                        )
                    )
                )
            LIMIT 1
            FOR UPDATE
            "
        );

    if (!$activeStatement) {
        throw new RuntimeException(
            "Unable to prepare active-loan validation."
        );
    }

    mysqli_stmt_bind_param(
        $activeStatement,
        "ii",
        $memberID,
        $productLoanID
    );

    mysqli_stmt_execute(
        $activeStatement
    );

    $otherActiveLoan =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $activeStatement
            )
        );

    mysqli_stmt_close(
        $activeStatement
    );

    if ($otherActiveLoan) {
        throw new ProductLoanUpdateException(
            $member["full_name"] .
            " still has another active Product Loan.",
            409
        );
    }


/* =====================================================
   AUTHORITATIVE PRODUCT LIST VALIDATION

   Product name and unit price are taken from
   Product List on the server.

   Requested quantity is the number of units the
   member wants to borrow. Product List does not
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
                "Unable to prepare Product List validation."
            );
        }


        $authoritativeItems = [];
        $usedCatalogProductIDs = [];
        $authoritativePrincipalCents = 0;


        foreach (
            $decodedItems as $item
        ) {

            if (!is_array($item)) {

                throw new ProductLoanUpdateException(
                    "A requested product contains invalid data."
                );
            }


            $quantityRaw =
                $item["quantity"]
                ?? null;


            if (
                filter_var(
                    $quantityRaw,
                    FILTER_VALIDATE_INT
                ) === false
            ) {

                throw new ProductLoanUpdateException(
                    "Every quantity must be a whole number."
                );
            }


            $requestedQuantity =
                (int) $quantityRaw;


            if (
                $requestedQuantity <= 0 ||
                $requestedQuantity > 1000000
            ) {

                throw new ProductLoanUpdateException(
                    "Every quantity must be between 1 and 1,000,000."
                );
            }


            $submittedProductID =
                filter_var(
                    $item["loan_product_id"]
                    ?? null,
                    FILTER_VALIDATE_INT
                );


            $submittedProductName =
                updateProductLoanNormalizeText(
                    $item["product_name"]
                    ?? ""
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
                    "i",
                    $submittedProductID
                );


                if (
                    !mysqli_stmt_execute(
                        $catalogByIDStatement
                    )
                ) {

                    throw new RuntimeException(
                        "Unable to validate a selected Product List item."
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
                $submittedProductName !== ""
            ) {

                mysqli_stmt_bind_param(
                    $catalogByNameStatement,
                    "s",
                    $submittedProductName
                );


                if (
                    !mysqli_stmt_execute(
                        $catalogByNameStatement
                    )
                ) {

                    throw new RuntimeException(
                        "Unable to validate a requested product."
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

                throw new ProductLoanUpdateException(
                    $submittedProductName !== ""
                        ? (
                            $submittedProductName .
                            " is no longer listed in Product List."
                        )
                        : "A selected Product List item could not be found.",
                    409
                );
            }


            $catalogProductID =
                (int) (
                    $catalogProduct[
                        "loan_product_id"
                    ]
                    ?? 0
                );


            $catalogProductName =
                updateProductLoanNormalizeText(
                    $catalogProduct[
                        "product_name"
                    ]
                    ?? ""
                );


            if (
                $catalogProductID <= 0 ||
                $catalogProductName === ""
            ) {

                throw new RuntimeException(
                    "A Product List item contains invalid data."
                );
            }


            if (
                isset(
                    $usedCatalogProductIDs[
                        $catalogProductID
                    ]
                )
            ) {

                throw new ProductLoanUpdateException(
                    $catalogProductName .
                    " was added more than once."
                );
            }


            $usedCatalogProductIDs[
                $catalogProductID
            ] = true;


            $unitPriceCents =
                updateProductLoanMoneyToCents(
                    $catalogProduct[
                        "loan_price"
                    ]
                    ?? null
                );


            $lineTotalCents =
                $requestedQuantity *
                $unitPriceCents;


            $authoritativePrincipalCents +=
                $lineTotalCents;


            if (
                $authoritativePrincipalCents >
                999999999999999
            ) {

                throw new ProductLoanUpdateException(
                    "The total loan amount exceeds the allowed value."
                );
            }


            $authoritativeItems[] = [
                "loan_product_id" =>
                    $catalogProductID,

                "product_name" =>
                    $catalogProductName,

                "quantity" =>
                    $requestedQuantity,

                "unit_price" =>
                    updateProductLoanCentsToAmount(
                        $unitPriceCents
                    ),

                "line_total" =>
                    updateProductLoanCentsToAmount(
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


        $validatedItems =
            $authoritativeItems;


        $principalCents =
            $authoritativePrincipalCents;


        $scheduledInterestCents =
            (int) round(
                $principalCents *
                (
                    $interestRate / 100
                )
            );


        $scheduledTotalCents =
            $principalCents +
            $scheduledInterestCents;


        $principalAmount =
            updateProductLoanCentsToAmount(
                $principalCents
            );


        $scheduledInterestAmount =
            updateProductLoanCentsToAmount(
                $scheduledInterestCents
            );


        $scheduledTotalAmount =
            updateProductLoanCentsToAmount(
                $scheduledTotalCents
            );


    /* VERIFY REMOVED ATTACHMENTS */

    $attachmentIDStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT product_loan_attachment_id
            FROM product_loan_attachments
            WHERE
                product_loan_id = ?
                AND is_removed = 0
            FOR UPDATE
            "
        );

    if (!$attachmentIDStatement) {
        throw new RuntimeException(
            "Unable to prepare attachment validation."
        );
    }

    mysqli_stmt_bind_param(
        $attachmentIDStatement,
        "i",
        $productLoanID
    );

    mysqli_stmt_execute(
        $attachmentIDStatement
    );

    $attachmentIDResult =
        mysqli_stmt_get_result(
            $attachmentIDStatement
        );

    $validExistingAttachmentIDs = [];

    while (
        $attachmentRow =
            mysqli_fetch_assoc(
                $attachmentIDResult
            )
    ) {
        $validExistingAttachmentIDs[] =
            (int) $attachmentRow[
                "product_loan_attachment_id"
            ];
    }

    mysqli_stmt_close(
        $attachmentIDStatement
    );

    foreach (
        $removedAttachmentIDs
        as $removedAttachmentID
    ) {
        if (
            !in_array(
                $removedAttachmentID,
                $validExistingAttachmentIDs,
                true
            )
        ) {
            throw new ProductLoanUpdateException(
                "An attachment selected for removal is invalid."
            );
        }
    }

    $remainingExistingAttachmentCount =
        count($validExistingAttachmentIDs) -
        count($removedAttachmentIDs);

    $totalAttachmentCountAfterUpdate =
        $remainingExistingAttachmentCount +
        count($newUploads);

    if ($totalAttachmentCountAfterUpdate < 1) {
        throw new ProductLoanUpdateException(
            "At least one supporting document must remain attached to the Product Loan application."
        );
    }


    /* STATUS DATES */

    $serverDate =
        date("Y-m-d");

    $serverDateTime =
        date("Y-m-d H:i:s");

    $dueDate = null;

    if ($newStatus === "Delivered") {
        $dueDate =
            (
                new DateTimeImmutable(
                    $serverDate,
                    new DateTimeZone(
                        "Asia/Manila"
                    )
                )
            )
            ->modify(
                "+" .
                $termDays .
                " days"
            )
            ->format(
                "Y-m-d"
            );
    }


    /* UPDATE PRODUCT LOAN */

    if ($newStatus === "Delivered") {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE product_loans
                SET
                    member_id = ?,
                    interest_rate = ?,
                    term_days = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = ?,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Delivered',
                    delivered_date = ?,
                    due_date = ?,
                    last_interest_date = ?,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    rejected_at = NULL,
                    updated_by = ?
                WHERE product_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the delivery update."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "ididdddsssii",
            $memberID,
            $interestRate,
            $termDays,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $principalAmount,
            $serverDate,
            $dueDate,
            $serverDate,
            $userID,
            $productLoanID
        );
    } elseif ($newStatus === "Rejected") {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE product_loans
                SET
                    member_id = ?,
                    interest_rate = ?,
                    term_days = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = 0.00,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Rejected',
                    delivered_date = NULL,
                    due_date = NULL,
                    last_interest_date = NULL,
                    rejection_reason = ?,
                    rejected_by = ?,
                    rejected_at = ?,
                    updated_by = ?
                WHERE product_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the rejection update."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "ididddsisii",
            $memberID,
            $interestRate,
            $termDays,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $rejectionReason,
            $userID,
            $serverDateTime,
            $userID,
            $productLoanID
        );
    } else {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE product_loans
                SET
                    member_id = ?,
                    interest_rate = ?,
                    term_days = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = ?,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Pending',
                    delivered_date = NULL,
                    due_date = NULL,
                    last_interest_date = NULL,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    rejected_at = NULL,
                    updated_by = ?
                WHERE product_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the pending update."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "ididdddii",
            $memberID,
            $interestRate,
            $termDays,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $principalAmount,
            $userID,
            $productLoanID
        );
    }

    if (
        !mysqli_stmt_execute(
            $updateStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to update the Product Loan."
        );
    }

    mysqli_stmt_close(
        $updateStatement
    );


    /* REPLACE REQUESTED PRODUCTS */

    $deleteItemsStatement =
        mysqli_prepare(
            $conn,
            "
            DELETE FROM product_loan_items
            WHERE product_loan_id = ?
            "
        );

    if (!$deleteItemsStatement) {
        throw new RuntimeException(
            "Unable to prepare product replacement."
        );
    }

    mysqli_stmt_bind_param(
        $deleteItemsStatement,
        "i",
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $deleteItemsStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to replace the requested products."
        );
    }

    mysqli_stmt_close(
        $deleteItemsStatement
    );

    $insertItemStatement =
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

    if (!$insertItemStatement) {
        throw new RuntimeException(
            "Unable to prepare updated products."
        );
    }

    foreach ($validatedItems as $item) {
        $itemName =
            $item["product_name"];

        $itemQuantity =
            $item["quantity"];

        $itemUnitPrice =
            $item["unit_price"];

        $itemLineTotal =
            $item["line_total"];

        mysqli_stmt_bind_param(
            $insertItemStatement,
            "isidd",
            $productLoanID,
            $itemName,
            $itemQuantity,
            $itemUnitPrice,
            $itemLineTotal
        );

        if (
            !mysqli_stmt_execute(
                $insertItemStatement
            )
        ) {
            throw new RuntimeException(
                "Unable to update the requested products."
            );
        }
    }

    mysqli_stmt_close(
        $insertItemStatement
    );


    /* SOFT-REMOVE ATTACHMENTS */

    if (count($removedAttachmentIDs) > 0) {
        $removeAttachmentStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE product_loan_attachments
                SET
                    is_removed = 1,
                    removed_by = ?,
                    removed_at = ?,
                    removal_reason =
                        'Removed while Product Loan was Pending.'
                WHERE
                    product_loan_attachment_id = ?
                    AND product_loan_id = ?
                    AND is_removed = 0
                "
            );

        if (!$removeAttachmentStatement) {
            throw new RuntimeException(
                "Unable to prepare attachment removal."
            );
        }

        foreach (
            $removedAttachmentIDs
            as $removedAttachmentID
        ) {
            mysqli_stmt_bind_param(
                $removeAttachmentStatement,
                "isii",
                $userID,
                $serverDateTime,
                $removedAttachmentID,
                $productLoanID
            );

            if (
                !mysqli_stmt_execute(
                    $removeAttachmentStatement
                )
            ) {
                throw new RuntimeException(
                    "Unable to remove an attachment."
                );
            }
        }

        mysqli_stmt_close(
            $removeAttachmentStatement
        );
    }


    /* SAVE NEW ATTACHMENTS */

    if (count($newUploads) > 0) {
        $systemRoot =
            dirname(
                __DIR__,
                2
            );

        $attachmentBaseDirectory =
            $systemRoot .
            DIRECTORY_SEPARATOR .
            "assets" .
            DIRECTORY_SEPARATOR .
            "documents" .
            DIRECTORY_SEPARATOR .
            "lending" .
            DIRECTORY_SEPARATOR .
            "product_loan";

        updateProductLoanEnsureDirectory(
            $attachmentBaseDirectory,
            "Unable to create the Product Loan document folder."
        );

        $securityFilePath =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            ".htaccess";

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
                    "Unable to secure the Product Loan document folder."
                );
            }
        }

        $memberFolderName =
            updateProductLoanCleanFolderName(
                (string) $member["member_code"]
            );

        $loanFolderName =
            "PL-" .
            str_pad(
                (string) $productLoanID,
                6,
                "0",
                STR_PAD_LEFT
            );

        $memberDirectory =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            $memberFolderName;

        $memberDirectoryAlreadyExisted =
            is_dir($memberDirectory);

        updateProductLoanEnsureDirectory(
            $memberDirectory,
            "Unable to create the member's Product Loan document folder."
        );

        if (
            !$memberDirectoryAlreadyExisted
        ) {
            $createdDirectories[] =
                $memberDirectory;
        }

        $attachmentDirectory =
            $memberDirectory .
            DIRECTORY_SEPARATOR .
            $loanFolderName;

        $attachmentDirectoryAlreadyExisted =
            is_dir($attachmentDirectory);

        updateProductLoanEnsureDirectory(
            $attachmentDirectory,
            "Unable to create the attachment folder."
        );

        if (
            !$attachmentDirectoryAlreadyExisted
        ) {
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
                "Unable to prepare new attachments."
            );
        }

        $reservedStoredFileNames = [];

        foreach ($newUploads as $file) {
            /*
            * Kuhanin ang hash bago ilipat ang file.
            * Ito ang authoritative duplicate check.
            */
            $incomingFileHash =
                hash_file(
                    'sha256',
                    $file['tmp_name']
                );

            if (
                !is_string($incomingFileHash) ||
                $incomingFileHash === ''
            ) {
                throw new RuntimeException(
                    'Unable to verify a new attachment.'
                );
            }


            /*
            * Check kung nakakabit na ang exact same file
            * sa Product Loan na ito.
            *
            * Hindi kasama ang soft-removed attachments.
            */
            $duplicateHashStatement =
                mysqli_prepare(
                    $conn,
                    "
                    SELECT
                        product_loan_attachment_id

                    FROM product_loan_attachments

                    WHERE
                        product_loan_id = ?
                        AND file_hash = ?
                        AND is_removed = 0

                    LIMIT 1
                    "
                );

            if (!$duplicateHashStatement) {
                throw new RuntimeException(
                    'Unable to check duplicate attachments.'
                );
            }

            mysqli_stmt_bind_param(
                $duplicateHashStatement,
                'is',
                $productLoanID,
                $incomingFileHash
            );

            mysqli_stmt_execute(
                $duplicateHashStatement
            );

            $duplicateHashResult =
                mysqli_stmt_get_result(
                    $duplicateHashStatement
                );

            $duplicateAttachment =
                mysqli_fetch_assoc(
                    $duplicateHashResult
                );

            mysqli_stmt_close(
                $duplicateHashStatement
            );

            if ($duplicateAttachment) {
                throw new ProductLoanUpdateException(
                    'This file is already attached: ' .
                    $file['original_name']
                );
            }


            /*
            * Gumawa ng readable at collision-safe filename.
            *
            * Example:
            * Member Document.docx
            * Member Document (2).docx
            * Member Document (3).docx
            */
            $storedFileName =
                updateProductLoanCreateReadableStoredFileName(
                    $file['original_name'],
                    $file['extension'],
                    $attachmentDirectory,
                    $reservedStoredFileNames
                );

            $absolutePath =
                $attachmentDirectory .
                DIRECTORY_SEPARATOR .
                $storedFileName;

            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $absolutePath
                )
            ) {
                throw new RuntimeException(
                    'Unable to store a new attachment.'
                );
            }

            $movedFiles[] =
                $absolutePath;


            /*
            * Verify ulit pagkatapos mailipat.
            */
            $storedFileHash =
                hash_file(
                    'sha256',
                    $absolutePath
                );

            if (
                !is_string($storedFileHash) ||
                !hash_equals(
                    $incomingFileHash,
                    $storedFileHash
                )
            ) {
                throw new RuntimeException(
                    'Unable to verify a new attachment after storage.'
                );
            }

            $relativePath =
                'assets/documents/lending/product_loan/' .
                $memberFolderName .
                '/' .
                $loanFolderName .
                '/' .
                $storedFileName;

            $originalName =
                $file['original_name'];

            $mimeType =
                $file['mime_type'];

            $fileSize =
                $file['file_size'];

            $fileHash =
                $storedFileHash;

            mysqli_stmt_bind_param(
                $attachmentStatement,
                'issssisi',
                $productLoanID,
                $originalName,
                $storedFileName,
                $relativePath,
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
                    'Unable to record a new attachment.'
                );
            }
        }

        mysqli_stmt_close(
            $attachmentStatement
        );

    }


    /* STATUS HISTORY */

    if ($newStatus !== "Pending") {
        $statusReason =
            $newStatus === "Delivered"
                ? (
                    "Products marked as Delivered. " .
                    "Interest started on " .
                    $serverDate .
                    " and due date was set to " .
                    $dueDate .
                    "."
                )
                : $rejectionReason;

        $statusHistoryStatement =
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
                    'Pending',
                    ?,
                    ?,
                    ?
                )
                "
            );

        if (!$statusHistoryStatement) {
            throw new RuntimeException(
                "Unable to prepare status history."
            );
        }

        mysqli_stmt_bind_param(
            $statusHistoryStatement,
            "isis",
            $productLoanID,
            $newStatus,
            $userID,
            $statusReason
        );

        if (
            !mysqli_stmt_execute(
                $statusHistoryStatement
            )
        ) {
            throw new RuntimeException(
                "Unable to save the status history."
            );
        }

        mysqli_stmt_close(
            $statusHistoryStatement
        );
    }


    /* =========================================================
    SAVE PRODUCT LOAN HISTORY LOG
    ========================================================= */

    $loanReference =
        "PL-" .
        str_pad(
            (string) $productLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );


    /* Final requested products snapshot */

    $productHistoryParts = [];

    foreach ($validatedItems as $validatedItem) {
        $productHistoryParts[] =
            $validatedItem["product_name"] .
            " × " .
            number_format(
                (int) $validatedItem["quantity"]
            ) .
            " at ₱" .
            number_format(
                (float) $validatedItem["unit_price"],
                2
            );
    }

    $productHistoryText =
        implode(
            ", ",
            $productHistoryParts
        );


    /* Attachment changes */

    $attachmentChanges = [];

    $newAttachmentCount =
        count(
            $newUploads
        );

    $removedAttachmentCount =
        count(
            $removedAttachmentIDs
        );

    if ($newAttachmentCount > 0) {
        $attachmentChanges[] =
            "added " .
            number_format(
                $newAttachmentCount
            ) .
            (
                $newAttachmentCount === 1
                    ? " new attachment"
                    : " new attachments"
            );
    }

    if ($removedAttachmentCount > 0) {
        $attachmentChanges[] =
            "removed " .
            number_format(
                $removedAttachmentCount
            ) .
            (
                $removedAttachmentCount === 1
                    ? " attachment"
                    : " attachments"
            );
    }

    $attachmentHistoryText =
        count($attachmentChanges) > 0
            ? (
                " Attachment changes: " .
                implode(
                    " and ",
                    $attachmentChanges
                ) .
                "."
            )
            : " No attachment changes.";


    /* Main activity message */

    if ($newStatus === "Delivered") {
        $historyMessage =
            'marked Product Loan "' .
            $loanReference .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            ') as Delivered. Products: ' .
            $productHistoryText .
            ". Principal ₱" .
            number_format(
                $principalAmount,
                2
            ) .
            ", interest " .
            number_format(
                $interestRate,
                0
            ) .
            "% for " .
            number_format(
                $termDays
            ) .
            " days, scheduled total ₱" .
            number_format(
                $scheduledTotalAmount,
                2
            ) .
            ", delivered on " .
            date(
                "m-d-Y",
                strtotime($serverDate)
            ) .
            ", and due on " .
            date(
                "m-d-Y",
                strtotime($dueDate)
            ) .
            "." .
            $attachmentHistoryText;
    } elseif ($newStatus === "Rejected") {
        $historyMessage =
            'rejected Product Loan "' .
            $loanReference .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            '). Reason: "' .
            $rejectionReason .
            '". Products: ' .
            $productHistoryText .
            ". Principal ₱" .
            number_format(
                $principalAmount,
                2
            ) .
            ", interest " .
            number_format(
                $interestRate,
                0
            ) .
            "% for " .
            number_format(
                $termDays
            ) .
            " days, scheduled total ₱" .
            number_format(
                $scheduledTotalAmount,
                2
            ) .
            "." .
            $attachmentHistoryText;
    } else {
        $historyMessage =
            'updated Pending Product Loan "' .
            $loanReference .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            '). Products: ' .
            $productHistoryText .
            ". Principal ₱" .
            number_format(
                $principalAmount,
                2
            ) .
            ", interest " .
            number_format(
                $interestRate,
                0
            ) .
            "% for " .
            number_format(
                $termDays
            ) .
            " days, scheduled total ₱" .
            number_format(
                $scheduledTotalAmount,
                2
            ) .
            "." .
            $attachmentHistoryText;
    }

    log_activity(
        $conn,
        "Lending",
        "Updated",
        $historyMessage,
        $productLoanID
    );


    /* =========================================================
    COMMIT
    ========================================================= */

    mysqli_commit(
        $conn
    );

    $transactionStarted = false;

    $message =
        "The pending Product Loan was updated.";

    if ($newStatus === "Delivered") {
        $message =
            "The products were marked as Delivered. " .
            "Interest starts today and the due date is " .
            date(
                "m-d-Y",
                strtotime($dueDate)
            ) .
            ".";
    } elseif ($newStatus === "Rejected") {
        $message =
            "The Product Loan application was rejected and remains recorded in the ledger.";
    }

    updateProductLoanResponse(
        true,
        $message,
        [
            "product_loan_id" =>
                $productLoanID,

            "status" =>
                $newStatus,

            "delivered_date" =>
                $newStatus === "Delivered"
                    ? $serverDate
                    : null,

            "due_date" =>
                $dueDate
        ]
    );
} catch (
    ProductLoanUpdateException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    foreach ($movedFiles as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    foreach (
        array_reverse($createdDirectories)
        as $createdDirectory
    ) {
        if (is_dir($createdDirectory)) {
            @rmdir($createdDirectory);
        }
    }

    updateProductLoanResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );
} catch (Throwable $exception) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    foreach ($movedFiles as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    foreach (
        array_reverse($createdDirectories)
        as $createdDirectory
    ) {
        if (is_dir($createdDirectory)) {
            @rmdir($createdDirectory);
        }
    }

    error_log(
        "Update Product Loan error: " .
        $exception->getMessage()
    );

    updateProductLoanResponse(
        false,
        "Unable to update the Product Loan. Please try again.",
        [],
        500
    );
}