<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

ob_start();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

register_shutdown_function(
    static function (): void {
        $lastError = error_get_last();

        if (
            !is_array($lastError) ||
            !in_array(
                (int) ($lastError["type"] ?? 0),
                [
                    E_ERROR,
                    E_PARSE,
                    E_CORE_ERROR,
                    E_COMPILE_ERROR,
                    E_USER_ERROR
                ],
                true
            )
        ) {
            return;
        }

        error_log(
            "Update Monetary Loan fatal error: " .
            (string) ($lastError["message"] ?? "Unknown PHP error") .
            " on line " .
            (string) ($lastError["line"] ?? 0)
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header("Content-Type: application/json; charset=utf-8");
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        }

        http_response_code(500);

        echo json_encode(
            [
                "success" => false,
                "message" =>
                    "A server error prevented the Monetary Loan from being updated. Check the Apache error log."
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
);


/* =========================================================
   CUSTOM EXCEPTION
========================================================= */

class MonetaryLoanUpdateException extends RuntimeException
{
    private int $httpStatusCode;

    public function __construct(
        string $message,
        int $httpStatusCode = 422
    ) {
        parent::__construct($message);

        $this->httpStatusCode =
            $httpStatusCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}


/* =========================================================
   JSON RESPONSE
========================================================= */

function monetaryLoanUpdateResponse(
    bool $success,
    string $message,
    array $data = [],
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

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


/* =========================================================
   TEXT HELPERS
========================================================= */

function monetaryLoanUpdateNormalizeText($value): string
{
    $value =
        trim((string) $value);

    $normalized =
        preg_replace(
            "/\s+/u",
            " ",
            $value
        );

    return is_string($normalized)
        ? $normalized
        : $value;
}


function monetaryLoanUpdateTextLength(string $value): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}


function monetaryLoanUpdateCleanFileName(
    string $fileName
): string {
    $fileName =
        basename($fileName);

    $fileName =
        preg_replace(
            '/[\x00-\x1F\x7F]/u',
            "",
            $fileName
        );

    $fileName =
        trim((string) $fileName);

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


/* =========================================================
   FILE NAME HELPERS
========================================================= */

function monetaryLoanUpdateFileExtension(
    string $fileName
): string {
    return strtolower(
        (string) pathinfo(
            basename($fileName),
            PATHINFO_EXTENSION
        )
    );
}


function monetaryLoanUpdateHasDangerousEmbeddedExtension(
    string $fileName
): bool {
    return preg_match(
        '/\.(php[0-9]*|phtml|phar|cgi|pl|py|sh|exe|com|bat|cmd|msi|js|html?|svg|sql)(?:\.|$)/i',
        basename($fileName)
    ) === 1;
}


function monetaryLoanUpdateSanitizeBaseName(
    string $originalFileName
): string {
    $baseName =
        (string) pathinfo(
            basename($originalFileName),
            PATHINFO_FILENAME
        );

    $baseName =
        preg_replace(
            '~[\x00-\x1F\x7F<>:"/\\\\|?*]+~u',
            " ",
            $baseName
        );

    $baseName =
        preg_replace(
            "/\s+/u",
            " ",
            (string) $baseName
        );

    $baseName =
        trim(
            (string) $baseName,
            " .\t\n\r\0\x0B"
        );

    $reservedWindowsNames = [
        "con",
        "prn",
        "aux",
        "nul",
        "com1",
        "com2",
        "com3",
        "com4",
        "com5",
        "com6",
        "com7",
        "com8",
        "com9",
        "lpt1",
        "lpt2",
        "lpt3",
        "lpt4",
        "lpt5",
        "lpt6",
        "lpt7",
        "lpt8",
        "lpt9"
    ];

    if (
        $baseName === "" ||
        in_array(
            strtolower($baseName),
            $reservedWindowsNames,
            true
        )
    ) {
        $baseName = "attachment";
    }

    $baseName =
        function_exists("mb_substr")
            ? mb_substr(
                $baseName,
                0,
                160,
                "UTF-8"
            )
            : substr(
                $baseName,
                0,
                160
            );

    $baseName =
        rtrim(
            $baseName,
            " ."
        );

    return $baseName !== ""
        ? $baseName
        : "attachment";
}


function monetaryLoanUpdateCreateStoredFileName(
    string $originalFileName,
    string $extension,
    string $directory,
    array &$reservedNames
): string {
    $baseName =
        monetaryLoanUpdateSanitizeBaseName(
            $originalFileName
        );

    $counter = 1;

    do {
        $suffix =
            $counter === 1
                ? ""
                : " (" . $counter . ")";

        $candidate =
            $baseName .
            $suffix .
            "." .
            strtolower($extension);

        $candidateKey =
            strtolower($candidate);

        $alreadyReserved =
            isset(
                $reservedNames[
                    $candidateKey
                ]
            );

        $alreadyExists =
            is_file(
                $directory .
                DIRECTORY_SEPARATOR .
                $candidate
            );

        $counter++;
    } while (
        $alreadyReserved ||
        $alreadyExists
    );

    $reservedNames[
        $candidateKey
    ] = true;

    return $candidate;
}


/* =========================================================
   FILE CONTENT HELPERS
========================================================= */

function monetaryLoanUpdateFileStartsWith(
    string $filePath,
    string $expectedBytes
): bool {
    $handle =
        @fopen(
            $filePath,
            "rb"
        );

    if (!$handle) {
        return false;
    }

    try {
        $actualBytes =
            fread(
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


function monetaryLoanUpdateIsValidLegacyDoc(
    string $filePath
): bool {
    if (
        !monetaryLoanUpdateFileStartsWith(
            $filePath,
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
        )
    ) {
        return false;
    }

    $contents =
        @file_get_contents($filePath);

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


function monetaryLoanUpdateIsValidDocx(
    string $filePath
): bool {
    if (!class_exists("ZipArchive")) {
        throw new RuntimeException(
            "The PHP ZIP extension is unavailable. Enable extension=zip in php.ini."
        );
    }

    $archive =
        new ZipArchive();

    $opened =
        $archive->open(
            $filePath,
            ZipArchive::RDONLY
        );

    if ($opened !== true) {
        return false;
    }

    try {
        $requiredFiles = [
            "[Content_Types].xml",
            "_rels/.rels",
            "word/document.xml"
        ];

        foreach (
            $requiredFiles
            as $requiredFile
        ) {
            if (
                $archive->locateName(
                    $requiredFile
                ) === false
            ) {
                return false;
            }
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


/* =========================================================
   DIRECTORY HELPERS
========================================================= */

function monetaryLoanUpdateCleanFolderName(
    string $memberCode
): string {
    $folderName =
        preg_replace(
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


function monetaryLoanUpdateEnsureDirectory(
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


/* =========================================================
   NORMALIZE UPLOADS
========================================================= */

function monetaryLoanUpdateNormalizeUploads(
    string $fieldName
): array {
    if (
        !isset($_FILES[$fieldName]) ||
        !isset(
            $_FILES[$fieldName]["name"]
        )
    ) {
        return [];
    }

    $files =
        $_FILES[$fieldName];

    $normalizedFiles = [];

    if (is_array($files["name"])) {
        $fileCount =
            count($files["name"]);

        for (
            $index = 0;
            $index < $fileCount;
            $index++
        ) {
            $uploadError =
                isset(
                    $files["error"][$index]
                )
                    ? (int) $files["error"][$index]
                    : UPLOAD_ERR_NO_FILE;

            if (
                $uploadError ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $normalizedFiles[] = [
                "name" =>
                    $files["name"][$index] ?? "",

                "tmp_name" =>
                    $files["tmp_name"][$index] ?? "",

                "error" =>
                    $uploadError,

                "size" =>
                    isset(
                        $files["size"][$index]
                    )
                        ? (int) $files["size"][$index]
                        : 0
            ];
        }

        return $normalizedFiles;
    }

    $uploadError =
        isset($files["error"])
            ? (int) $files["error"]
            : UPLOAD_ERR_NO_FILE;

    if (
        $uploadError !==
        UPLOAD_ERR_NO_FILE
    ) {
        $normalizedFiles[] = [
            "name" =>
                $files["name"] ?? "",

            "tmp_name" =>
                $files["tmp_name"] ?? "",

            "error" =>
                $uploadError,

            "size" =>
                isset($files["size"])
                    ? (int) $files["size"]
                    : 0
        ];
    }

    return $normalizedFiles;
}


function monetaryLoanUpdateUploadError(
    int $error
): string {
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
                "A server extension stopped the attachment upload.";

        default:
            return
                "An attachment could not be uploaded.";
    }
}


/* =========================================================
   VALIDATE REAL FILE CONTENT
========================================================= */

function monetaryLoanUpdateValidateUploads(
    array $files
): array {
    $maximumFiles = 10;

    $maximumFileSize =
        10 * 1024 * 1024;

    $maximumTotalSize =
        50 * 1024 * 1024;

    $allowedExtensions = [
        "jpg",
        "jpeg",
        "png",
        "pdf",
        "doc",
        "docx"
    ];

    if (count($files) > $maximumFiles) {
        throw new MonetaryLoanUpdateException(
            "A maximum of 10 new attachments is allowed per update."
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

    $fileInfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );

    if (!$fileInfo) {
        throw new RuntimeException(
            "Unable to inspect the attachments."
        );
    }

    $validatedFiles = [];

    $totalSize = 0;

    try {
        foreach ($files as $file) {
            $uploadError =
                (int) (
                    $file["error"] ??
                    UPLOAD_ERR_NO_FILE
                );

            if (
                $uploadError !==
                UPLOAD_ERR_OK
            ) {
                throw new MonetaryLoanUpdateException(
                    monetaryLoanUpdateUploadError(
                        $uploadError
                    )
                );
            }

            $fileSize =
                (int) (
                    $file["size"] ?? 0
                );

            if ($fileSize <= 0) {
                throw new MonetaryLoanUpdateException(
                    "An attachment is empty."
                );
            }

            if (
                $fileSize >
                $maximumFileSize
            ) {
                throw new MonetaryLoanUpdateException(
                    "Each attachment must not exceed 10 MB."
                );
            }

            $totalSize +=
                $fileSize;

            if (
                $totalSize >
                $maximumTotalSize
            ) {
                throw new MonetaryLoanUpdateException(
                    "The combined size of new attachments must not exceed 50 MB."
                );
            }

            $temporaryPath =
                (string) (
                    $file["tmp_name"] ?? ""
                );

            $originalName =
                (string) (
                    $file["name"] ?? ""
                );

            $extension =
                monetaryLoanUpdateFileExtension(
                    $originalName
                );

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                throw new MonetaryLoanUpdateException(
                    "Only JPG, PNG, PDF, DOC, and DOCX attachments are allowed."
                );
            }

            if (
                monetaryLoanUpdateHasDangerousEmbeddedExtension(
                    $originalName
                )
            ) {
                throw new MonetaryLoanUpdateException(
                    "An unsafe attachment filename was detected."
                );
            }

            if (
                !is_uploaded_file(
                    $temporaryPath
                )
            ) {
                throw new MonetaryLoanUpdateException(
                    "An invalid attachment upload was detected.",
                    400
                );
            }

            $realMimeType =
                finfo_file(
                    $fileInfo,
                    $temporaryPath
                );

            if (!is_string($realMimeType)) {
                throw new MonetaryLoanUpdateException(
                    "Unable to identify an attachment's real file type."
                );
            }

            $storedExtension = "";
            $canonicalMimeType = "";

            switch ($extension) {
                case "jpg":
                case "jpeg":
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $realMimeType !== "image/jpeg" ||
                        !is_array($imageDetails) ||
                        (
                            $imageDetails["mime"] ?? ""
                        ) !== "image/jpeg"
                    ) {
                        throw new MonetaryLoanUpdateException(
                            "A JPG attachment does not contain a valid JPG image."
                        );
                    }

                    $storedExtension = "jpg";
                    $canonicalMimeType = "image/jpeg";

                    break;

                case "png":
                    $imageDetails =
                        @getimagesize(
                            $temporaryPath
                        );

                    if (
                        $realMimeType !== "image/png" ||
                        !is_array($imageDetails) ||
                        (
                            $imageDetails["mime"] ?? ""
                        ) !== "image/png"
                    ) {
                        throw new MonetaryLoanUpdateException(
                            "A PNG attachment does not contain a valid PNG image."
                        );
                    }

                    $storedExtension = "png";
                    $canonicalMimeType = "image/png";

                    break;

                case "pdf":
                    if (
                        !in_array(
                            $realMimeType,
                            [
                                "application/pdf",
                                "application/x-pdf"
                            ],
                            true
                        ) ||
                        !monetaryLoanUpdateFileStartsWith(
                            $temporaryPath,
                            "%PDF-"
                        )
                    ) {
                        throw new MonetaryLoanUpdateException(
                            "A PDF attachment does not contain a valid PDF document."
                        );
                    }

                    $storedExtension = "pdf";
                    $canonicalMimeType = "application/pdf";

                    break;

                case "doc":
                    $allowedDocMimeTypes = [
                        "application/msword",
                        "application/x-ole-storage",
                        "application/CDFV2",
                        "application/octet-stream"
                    ];

                    if (
                        !in_array(
                            $realMimeType,
                            $allowedDocMimeTypes,
                            true
                        ) ||
                        !monetaryLoanUpdateIsValidLegacyDoc(
                            $temporaryPath
                        )
                    ) {
                        throw new MonetaryLoanUpdateException(
                            "A DOC attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension = "doc";
                    $canonicalMimeType = "application/msword";

                    break;

                case "docx":
                    $allowedDocxMimeTypes = [
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "application/zip",
                        "application/octet-stream"
                    ];

                    if (
                        !in_array(
                            $realMimeType,
                            $allowedDocxMimeTypes,
                            true
                        ) ||
                        !monetaryLoanUpdateFileStartsWith(
                            $temporaryPath,
                            "PK"
                        ) ||
                        !monetaryLoanUpdateIsValidDocx(
                            $temporaryPath
                        )
                    ) {
                        throw new MonetaryLoanUpdateException(
                            "A DOCX attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension = "docx";

                    $canonicalMimeType =
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

                    break;
            }

            $validatedFiles[] = [
                "original_name" =>
                    monetaryLoanUpdateCleanFileName(
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
        finfo_close($fileInfo);
    }

    return $validatedFiles;
}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {
    monetaryLoanUpdateResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}

$requestedWith =
    strtolower(
        trim(
            (string) (
                $_SERVER[
                    "HTTP_X_REQUESTED_WITH"
                ] ?? ""
            )
        )
    );

if ($requestedWith !== "xmlhttprequest") {
    monetaryLoanUpdateResponse(
        false,
        "Invalid request.",
        [],
        400
    );
}

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {
    monetaryLoanUpdateResponse(
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
    monetaryLoanUpdateResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionCSRFToken =
    isset($_SESSION["csrf_token"])
        ? (string) $_SESSION["csrf_token"]
        : "";

$requestCSRFToken =
    isset($_POST["csrf_token"])
        ? (string) $_POST["csrf_token"]
        : "";

if (
    $sessionCSRFToken === "" ||
    $requestCSRFToken === "" ||
    !hash_equals(
        $sessionCSRFToken,
        $requestCSRFToken
    )
) {
    monetaryLoanUpdateResponse(
        false,
        "Your security token is invalid or has expired. Refresh the page and try again.",
        [],
        403
    );
}


/* =========================================================
   POST VALUES
========================================================= */

$monetaryLoanID =
    isset($_POST["monetary_loan_id"])
        ? (int) $_POST["monetary_loan_id"]
        : 0;

$memberID =
    isset($_POST["member_id"])
        ? (int) $_POST["member_id"]
        : 0;

$loanPurpose =
    monetaryLoanUpdateNormalizeText(
        $_POST["loan_purpose"] ?? ""
    );

$principalRaw =
    trim(
        (string) (
            $_POST["principal_amount"] ?? ""
        )
    );

$termDays =
    isset($_POST["term_days"])
        ? (int) $_POST["term_days"]
        : 0;

$loanTermYearsRaw =
    trim(
        (string) (
            $_POST["loan_term_years"]
            ?? ""
        )
    );

$loanTermYears = null;

$newStatus =
    monetaryLoanUpdateNormalizeText(
        $_POST["status"] ?? ""
    );

$rejectionReason =
    monetaryLoanUpdateNormalizeText(
        $_POST["rejection_reason"] ?? ""
    );

$removedAttachmentsJSON =
    trim(
        (string) (
            $_POST[
                "removed_attachment_ids"
            ] ?? "[]"
        )
    );


/* =========================================================
   SIMPLE FIELD VALIDATION
========================================================= */

if ($monetaryLoanID <= 0) {
    monetaryLoanUpdateResponse(
        false,
        "Invalid Monetary Loan ID.",
        [],
        422
    );
}

if ($memberID <= 0) {
    monetaryLoanUpdateResponse(
        false,
        "Please select an eligible Regular member.",
        [],
        422
    );
}

$allowedMonetaryLoanTypes = [
    "Crop Production Loan",
    "Dairy Farmers Loan",
    "Emergency Loan",
    "Long-Term Loan"
];

if (
    !in_array(
        $loanPurpose,
        $allowedMonetaryLoanTypes,
        true
    )
) {
    monetaryLoanUpdateResponse(
        false,
        "Please select a valid Monetary Loan type.",
        [],
        422
    );
}

if ($loanPurpose === "Long-Term Loan") {
    if (
        $loanTermYearsRaw === "" ||
        preg_match(
            "/^\d+$/",
            $loanTermYearsRaw
        ) !== 1
    ) {
        monetaryLoanUpdateResponse(
            false,
            "Enter a valid whole number of years for the Long-Term Loan.",
            [],
            422
        );
    }

    $loanTermYears =
        (int) $loanTermYearsRaw;

    if (
        $loanTermYears < 1 ||
        $loanTermYears > 65535
    ) {
        monetaryLoanUpdateResponse(
            false,
            "Enter a valid whole number of years for the Long-Term Loan.",
            [],
            422
        );
    }
}

if (!is_numeric($principalRaw)) {
    monetaryLoanUpdateResponse(
        false,
        "Enter a valid principal amount.",
        [],
        422
    );
}

$principalAmount =
    round(
        (float) $principalRaw,
        2
    );

if (
    !is_finite($principalAmount) ||
    $principalAmount <= 0
) {
    monetaryLoanUpdateResponse(
        false,
        "The principal amount must be greater than zero.",
        [],
        422
    );
}

if (
    $principalAmount >
    9999999999.99
) {
    monetaryLoanUpdateResponse(
        false,
        "The principal amount exceeds the allowed value.",
        [],
        422
    );
}

$termOptions = [
    180 => 10,
    360 => 20
];

if (!isset($termOptions[$termDays])) {
    monetaryLoanUpdateResponse(
        false,
        "Please select a valid loan term.",
        [],
        422
    );
}

$interestRate =
    (float) $termOptions[$termDays];

$allowedStatuses = [
    "Pending",
    "Accepted",
    "Rejected"
];

if (
    !in_array(
        $newStatus,
        $allowedStatuses,
        true
    )
) {
    monetaryLoanUpdateResponse(
        false,
        "Invalid Monetary Loan status.",
        [],
        422
    );
}

if (
    $newStatus === "Rejected" &&
    $rejectionReason === ""
) {
    monetaryLoanUpdateResponse(
        false,
        "Please enter the reason for rejection.",
        [],
        422
    );
}


/* =========================================================
   REMOVED ATTACHMENTS
========================================================= */

$removedAttachmentIDs =
    json_decode(
        $removedAttachmentsJSON,
        true
    );

if (
    json_last_error() !==
        JSON_ERROR_NONE ||
    !is_array($removedAttachmentIDs)
) {
    monetaryLoanUpdateResponse(
        false,
        "Invalid removed attachment data.",
        [],
        422
    );
}

$removedAttachmentIDs =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    "intval",
                    $removedAttachmentIDs
                ),
                static function (
                    int $attachmentID
                ): bool {
                    return $attachmentID > 0;
                }
            )
        )
    );


/* =========================================================
   CALCULATE VALUES
========================================================= */

if (
    $loanPurpose === "Long-Term Loan" &&
    $loanTermYears !== null
) {
    $paymentPeriods =
        $loanTermYears *
        (
            $termDays === 180
                ? 2
                : 1
        );

    /*
     * Long-Term diminishing balance:
     *
     * Equal principal installments.
     * Interest is based on the remaining
     * principal before each installment.
     */
    $scheduledInterestAmount =
        round(
            $principalAmount *
            ($interestRate / 100) *
            (($paymentPeriods + 1) / 2),
            2
        );
} else {
    $paymentPeriods = 1;

    $scheduledInterestAmount =
        round(
            $principalAmount *
            (
                $interestRate / 100
            ),
            2
        );
}

$scheduledTotalAmount =
    round(
        $principalAmount +
        $scheduledInterestAmount,
        2
    );

$maximumStoredAmount =
    9999999999999.99;

if (
    $scheduledInterestAmount >
        $maximumStoredAmount ||
    $scheduledTotalAmount >
        $maximumStoredAmount
) {
    monetaryLoanUpdateResponse(
        false,
        "The computed Long-Term Loan amount exceeds the system limit. Reduce the principal amount or loan term.",
        [],
        422
    );
}

try {
    $validatedUploads =
        monetaryLoanUpdateValidateUploads(
            monetaryLoanUpdateNormalizeUploads(
                "attachments"
            )
        );
} catch (
    MonetaryLoanUpdateException $exception
) {
    monetaryLoanUpdateResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );
} catch (Throwable $exception) {
    error_log(
        "Monetary Loan attachment validation: " .
        $exception->getMessage()
    );

    monetaryLoanUpdateResponse(
        false,
        "Unable to validate the attachments.",
        [],
        500
    );
}


/* =========================================================
   DATABASE AND HISTORY LOGGER
========================================================= */

require_once "../../db.php";
require_once dirname(__DIR__, 2) . "/includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanUpdateResponse(
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
   MAIN TRANSACTION
========================================================= */

try {
    mysqli_begin_transaction($conn);

    $transactionStarted = true;


    /* =====================================================
       ACCOUNT AND LENDING PERMISSION
    ===================================================== */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                lending_permission

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
        throw new MonetaryLoanUpdateException(
            "The logged-in account could not be found.",
            401
        );
    }

    $hasFullAccess =
        strtolower(
            trim(
                (string) (
                    $account[
                        "lending_permission"
                    ] ?? ""
                )
            )
        ) === "full";


    /* =====================================================
       LOCK MONETARY LOAN
    ===================================================== */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                status,
                member_id,
                loan_purpose,
                principal_amount,
                term_days

            FROM monetary_loans

            WHERE
                monetary_loan_id = ?
                AND is_archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$loanStatement) {
        throw new RuntimeException(
            "Unable to prepare Monetary Loan validation."
        );
    }

    mysqli_stmt_bind_param(
        $loanStatement,
        "i",
        $monetaryLoanID
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
        throw new MonetaryLoanUpdateException(
            "The Monetary Loan record could not be found.",
            404
        );
    }

    $existingStatus =
        (string) $existingLoan["status"];

    if ($existingStatus !== "Pending") {
        throw new MonetaryLoanUpdateException(
            "Accepted or Rejected Monetary Loans can no longer be edited.",
            409
        );
    }

    if (
        !$hasFullAccess &&
        $newStatus !== "Pending"
    ) {
        throw new MonetaryLoanUpdateException(
            "Your Lending Permission is limited. The status must remain Pending.",
            403
        );
    }


    /* =====================================================
        MONETARY LOAN MEMBER ELIGIBILITY

        Requirements:
        - Registered member
        - Not archived
        - Active
        - Regular classification

        The member classification stored in the database
        is the final authority. Current CBU balance does
        not determine Monetary Loan eligibility.
        ===================================================== */

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


        /* =====================================================
        MEMBER EXISTS
        ===================================================== */

        if (!$member) {
            throw new MonetaryLoanUpdateException(
                "The selected member could not be found.",
                404
            );
        }


        /* =====================================================
        ACTIVE MEMBER
        ===================================================== */

        $memberStatus =
            strtoupper(
                trim(
                    (string) (
                        $member["status"]
                        ?? ""
                    )
                )
            );

        if ($memberStatus !== "ACTIVE") {
            throw new MonetaryLoanUpdateException(
                "Only active members may apply for a Monetary Loan.",
                422
            );
        }


        /* =====================================================
        REGULAR MEMBER
        ===================================================== */

        $memberClassification =
            strtoupper(
                trim(
                    (string) (
                        $member["classification"]
                        ?? ""
                    )
                )
            );

        if ($memberClassification !== "REGULAR") {
            throw new MonetaryLoanUpdateException(
                "Only Regular members are eligible to apply for a Monetary Loan.",
                422
            );
        }


    /* =====================================================
    SAME MONETARY LOAN TYPE RESTRICTION

    A Pending loan may not be changed into a Monetary Loan
    type for which the member already has another active loan.

    The current loan is excluded from the check.
    ===================================================== */

    $activeSameTypeLoanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT monetary_loan_id

            FROM monetary_loans

            WHERE
                member_id = ?
                AND monetary_loan_id <> ?
                AND is_archived = 0
                AND loan_purpose = ?

                AND (
                    status = 'Pending'

                    OR (
                        status = 'Accepted'

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

    if (!$activeSameTypeLoanStatement) {
        throw new RuntimeException(
            "Unable to prepare same Monetary Loan type validation."
        );
    }

    mysqli_stmt_bind_param(
        $activeSameTypeLoanStatement,
        "iis",
        $memberID,
        $monetaryLoanID,
        $loanPurpose
    );

    mysqli_stmt_execute(
        $activeSameTypeLoanStatement
    );

    $otherActiveSameTypeLoan =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $activeSameTypeLoanStatement
            )
        );

    mysqli_stmt_close(
        $activeSameTypeLoanStatement
    );

    if ($otherActiveSameTypeLoan) {
        throw new MonetaryLoanUpdateException(
            $member["full_name"] .
            " still has another active " .
            $loanPurpose .
            ". The existing " .
            $loanPurpose .
            " must be fully paid before another one can be created.",
            409
        );
    }


    /* =====================================================
       VERIFY REMOVED ATTACHMENTS
    ===================================================== */

    $attachmentIDStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                monetary_loan_attachment_id

            FROM monetary_loan_attachments

            WHERE
                monetary_loan_id = ?
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
        $monetaryLoanID
    );

    mysqli_stmt_execute(
        $attachmentIDStatement
    );

    $attachmentIDResult =
        mysqli_stmt_get_result(
            $attachmentIDStatement
        );

    $validAttachmentIDs = [];

    while (
        $attachmentRow =
            mysqli_fetch_assoc(
                $attachmentIDResult
            )
    ) {
        $validAttachmentIDs[] =
            (int) $attachmentRow[
                "monetary_loan_attachment_id"
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
                $validAttachmentIDs,
                true
            )
        ) {
            throw new MonetaryLoanUpdateException(
                "An attachment selected for removal is invalid."
            );
        }
    }

    $finalAttachmentCount =
        count($validAttachmentIDs)
        -
        count($removedAttachmentIDs)
        +
        count($validatedUploads);


    if ($finalAttachmentCount < 1) {
        throw new MonetaryLoanUpdateException(
            "At least one supporting document must remain attached to the Monetary Loan.",
            422
        );
    }


    if ($finalAttachmentCount > 10) {
        throw new MonetaryLoanUpdateException(
            "A maximum of 10 supporting documents is allowed per Monetary Loan.",
            422
        );
    }


    /* =====================================================
       SERVER DATES
    ===================================================== */

    $serverDate =
        date("Y-m-d");

    $serverDateTime =
        date("Y-m-d H:i:s");

    $dueDate = null;

    if ($newStatus === "Accepted") {
        $dueDateTermDays =
            $loanPurpose === "Long-Term Loan"
                ? $termDays *
                $paymentPeriods
                : $termDays;

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
                $dueDateTermDays .
                " days"
            )
            ->format("Y-m-d");
    }


    /* =====================================================
       UPDATE AS ACCEPTED
    ===================================================== */

    if ($newStatus === "Accepted") {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE monetary_loans

                SET
                    member_id = ?,
                    loan_purpose = ?,
                    interest_rate = ?,
                    term_days = ?,
                    loan_term_years = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = ?,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Accepted',
                    release_date = ?,
                    due_date = ?,
                    last_interest_date = ?,
                    approved_by = ?,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    rejected_at = NULL,
                    fully_paid_at = NULL,
                    updated_by = ?

                WHERE monetary_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the Monetary Loan acceptance."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "isdiiddddsssiii",
            $memberID,
            $loanPurpose,
            $interestRate,
            $termDays,
            $loanTermYears,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $principalAmount,
            $serverDate,
            $dueDate,
            $serverDate,
            $userID,
            $userID,
            $monetaryLoanID
        );


    /* =====================================================
       UPDATE AS REJECTED
    ===================================================== */

    } elseif ($newStatus === "Rejected") {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE monetary_loans

                SET
                    member_id = ?,
                    loan_purpose = ?,
                    interest_rate = ?,
                    term_days = ?,
                    loan_term_years = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = 0.00,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Rejected',
                    release_date = NULL,
                    due_date = NULL,
                    last_interest_date = NULL,
                    approved_by = NULL,
                    rejection_reason = ?,
                    rejected_by = ?,
                    rejected_at = ?,
                    fully_paid_at = NULL,
                    updated_by = ?

                WHERE monetary_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the Monetary Loan rejection."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "isdiidddsisii",
            $memberID,
            $loanPurpose,
            $interestRate,
            $termDays,
            $loanTermYears,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $rejectionReason,
            $userID,
            $serverDateTime,
            $userID,
            $monetaryLoanID
        );


    /* =====================================================
       KEEP AS PENDING
    ===================================================== */

    } else {
        $updateStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE monetary_loans

                SET
                    member_id = ?,
                    loan_purpose = ?,
                    interest_rate = ?,
                    term_days = ?,
                    loan_term_years = ?,
                    principal_amount = ?,
                    scheduled_interest_amount = ?,
                    scheduled_total_amount = ?,
                    outstanding_principal_amount = ?,
                    unpaid_interest_amount = 0.00,
                    net_payments_amount = 0.00,
                    status = 'Pending',
                    release_date = NULL,
                    due_date = NULL,
                    last_interest_date = NULL,
                    approved_by = NULL,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    rejected_at = NULL,
                    fully_paid_at = NULL,
                    updated_by = ?

                WHERE monetary_loan_id = ?
                "
            );

        if (!$updateStatement) {
            throw new RuntimeException(
                "Unable to prepare the pending Monetary Loan update."
            );
        }

        mysqli_stmt_bind_param(
            $updateStatement,
            "isdiiddddii",
            $memberID,
            $loanPurpose,
            $interestRate,
            $termDays,
            $loanTermYears,
            $principalAmount,
            $scheduledInterestAmount,
            $scheduledTotalAmount,
            $principalAmount,
            $userID,
            $monetaryLoanID
        );
    }

    if (
        !mysqli_stmt_execute(
            $updateStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to update the Monetary Loan."
        );
    }

    mysqli_stmt_close(
        $updateStatement
    );


    /* =====================================================
       SOFT REMOVE ATTACHMENTS
    ===================================================== */

    if (count($removedAttachmentIDs) > 0) {
        $removeAttachmentStatement =
            mysqli_prepare(
                $conn,
                "
                UPDATE monetary_loan_attachments

                SET
                    is_removed = 1,
                    removed_by = ?,
                    removed_at = ?,
                    removal_reason =
                        'Removed while Monetary Loan was Pending.'

                WHERE
                    monetary_loan_attachment_id = ?
                    AND monetary_loan_id = ?
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
                $monetaryLoanID
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


    /* =====================================================
       SAVE NEW ATTACHMENTS
    ===================================================== */

    if (count($validatedUploads) > 0) {
        $systemRoot =
            dirname(
                __DIR__,
                2
            );

        $baseDirectory =
            $systemRoot .
            DIRECTORY_SEPARATOR .
            "assets" .
            DIRECTORY_SEPARATOR .
            "documents" .
            DIRECTORY_SEPARATOR .
            "lending" .
            DIRECTORY_SEPARATOR .
            "monetary";

        monetaryLoanUpdateEnsureDirectory(
            $baseDirectory,
            "Unable to create the Monetary Loan document folder."
        );

        $securityFile =
            $baseDirectory .
            DIRECTORY_SEPARATOR .
            ".htaccess";

        if (!is_file($securityFile)) {
            $securityRules =
                "Options -Indexes\n" .
                "Require all denied\n";

            if (
                file_put_contents(
                    $securityFile,
                    $securityRules,
                    LOCK_EX
                ) === false
            ) {
                throw new RuntimeException(
                    "Unable to secure the Monetary Loan document folder."
                );
            }
        }

        $memberFolder =
            monetaryLoanUpdateCleanFolderName(
                (string) $member[
                    "member_code"
                ]
            );

        $loanFolder =
            "ML-" .
            str_pad(
                (string) $monetaryLoanID,
                6,
                "0",
                STR_PAD_LEFT
            );

        $memberDirectory =
            $baseDirectory .
            DIRECTORY_SEPARATOR .
            $memberFolder;

        $memberDirectoryExisted =
            is_dir($memberDirectory);

        monetaryLoanUpdateEnsureDirectory(
            $memberDirectory,
            "Unable to create the member's Monetary Loan document folder."
        );

        if (!$memberDirectoryExisted) {
            $createdDirectories[] =
                $memberDirectory;
        }

        $loanDirectory =
            $memberDirectory .
            DIRECTORY_SEPARATOR .
            $loanFolder;

        $loanDirectoryExisted =
            is_dir($loanDirectory);

        monetaryLoanUpdateEnsureDirectory(
            $loanDirectory,
            "Unable to create the Monetary Loan attachment folder."
        );

        if (!$loanDirectoryExisted) {
            $createdDirectories[] =
                $loanDirectory;
        }

        $attachmentStatement =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO monetary_loan_attachments (
                    monetary_loan_id,
                    original_file_name,
                    stored_file_name,
                    file_path,
                    mime_type,
                    file_size,
                    file_hash,
                    uploaded_by
                )
                VALUES (
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
                "Unable to prepare the new Monetary Loan attachments."
            );
        }

        $reservedFileNames = [];

        foreach (
            $validatedUploads
            as $file
        ) {
            $incomingHash =
                hash_file(
                    "sha256",
                    $file["tmp_name"]
                );

            if (
                !is_string($incomingHash) ||
                $incomingHash === ""
            ) {
                throw new RuntimeException(
                    "Unable to verify a new attachment."
                );
            }

            $duplicateStatement =
                mysqli_prepare(
                    $conn,
                    "
                    SELECT
                        monetary_loan_attachment_id

                    FROM monetary_loan_attachments

                    WHERE
                        monetary_loan_id = ?
                        AND file_hash = ?
                        AND is_removed = 0

                    LIMIT 1
                    "
                );

            if (!$duplicateStatement) {
                throw new RuntimeException(
                    "Unable to check duplicate attachments."
                );
            }

            mysqli_stmt_bind_param(
                $duplicateStatement,
                "is",
                $monetaryLoanID,
                $incomingHash
            );

            mysqli_stmt_execute(
                $duplicateStatement
            );

            $duplicateAttachment =
                mysqli_fetch_assoc(
                    mysqli_stmt_get_result(
                        $duplicateStatement
                    )
                );

            mysqli_stmt_close(
                $duplicateStatement
            );

            if ($duplicateAttachment) {
                throw new MonetaryLoanUpdateException(
                    "This file is already attached: " .
                    $file["original_name"]
                );
            }

            $storedFileName =
                monetaryLoanUpdateCreateStoredFileName(
                    $file["original_name"],
                    $file["extension"],
                    $loanDirectory,
                    $reservedFileNames
                );

            $absolutePath =
                $loanDirectory .
                DIRECTORY_SEPARATOR .
                $storedFileName;

            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $absolutePath
                )
            ) {
                throw new RuntimeException(
                    "Unable to store a new attachment."
                );
            }

            $movedFiles[] =
                $absolutePath;

            $storedHash =
                hash_file(
                    "sha256",
                    $absolutePath
                );

            if (
                !is_string($storedHash) ||
                !hash_equals(
                    $incomingHash,
                    $storedHash
                )
            ) {
                throw new RuntimeException(
                    "Unable to verify a new attachment after storage."
                );
            }

            $relativePath =
                "assets/documents/lending/monetary/" .
                $memberFolder .
                "/" .
                $loanFolder .
                "/" .
                $storedFileName;

            $originalName =
                $file["original_name"];

            $mimeType =
                $file["mime_type"];

            $fileSize =
                (int) $file["file_size"];

            mysqli_stmt_bind_param(
                $attachmentStatement,
                "issssisi",
                $monetaryLoanID,
                $originalName,
                $storedFileName,
                $relativePath,
                $mimeType,
                $fileSize,
                $storedHash,
                $userID
            );

            if (
                !mysqli_stmt_execute(
                    $attachmentStatement
                )
            ) {
                throw new RuntimeException(
                    "Unable to record a new attachment."
                );
            }
        }

        mysqli_stmt_close(
            $attachmentStatement
        );
    }


    /* =====================================================
       STATUS HISTORY
    ===================================================== */

    if ($newStatus !== "Pending") {
        $statusReason =
            $newStatus === "Accepted"
                ? (
                    "Monetary Loan accepted on " .
                    $serverDate .
                    ". Due date was set to " .
                    $dueDate .
                    "."
                )
                : $rejectionReason;

        $statusHistoryStatement =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO monetary_loan_status_history (
                    monetary_loan_id,
                    previous_status,
                    new_status,
                    changed_by,
                    reason
                )
                VALUES (
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
                "Unable to prepare the Monetary Loan status history."
            );
        }

        mysqli_stmt_bind_param(
            $statusHistoryStatement,
            "isis",
            $monetaryLoanID,
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
                "Unable to save the Monetary Loan status history."
            );
        }

        mysqli_stmt_close(
            $statusHistoryStatement
        );
    }


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit($conn);

    $transactionStarted = false;


    /* =====================================================
       CENTRAL HISTORY LOG
    ===================================================== */

    $loanDisplayID =
        "ML-" .
        str_pad(
            (string) $monetaryLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );

    if ($newStatus === "Accepted") {
        $historyMessage =
            'accepted Monetary Loan "' .
            $loanDisplayID .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            "). Loan Type: " .
            $loanPurpose .
            (
                $loanPurpose === "Long-Term Loan" &&
                $loanTermYears !== null
                    ? ", term " .
                    $loanTermYears .
                    (
                        $loanTermYears === 1
                            ? " year"
                            : " years"
                    ) .
                    ", " .
                    $paymentPeriods .
                    (
                        $paymentPeriods === 1
                            ? " payment period"
                            : " payment periods"
                    )
                    : ""
            ) .
            ". Principal " .
            "₱" .
            number_format(
                $principalAmount,
                2
            ) .
            ", interest " .
            number_format(
                $interestRate,
                0
            ) .
            (
                $loanPurpose === "Long-Term Loan"
                    ? "% every "
                    : "% for "
            ) .
            $termDays .
            " days, scheduled total ₱" .
            number_format(
                $scheduledTotalAmount,
                2
            ) .
            ", accepted on " .
            date(
                "m-d-Y",
                strtotime($serverDate)
            ) .
            ", and due on " .
            date(
                "m-d-Y",
                strtotime($dueDate)
            ) .
            ".";

    } elseif ($newStatus === "Rejected") {
        $historyMessage =
            'rejected Monetary Loan "' .
            $loanDisplayID .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            "). Loan Type: " .
            $loanPurpose .
            (
                $loanPurpose === "Long-Term Loan" &&
                $loanTermYears !== null
                    ? ", term " .
                    $loanTermYears .
                    (
                        $loanTermYears === 1
                            ? " year"
                            : " years"
                    ) .
                    ", " .
                    $paymentPeriods .
                    (
                        $paymentPeriods === 1
                            ? " payment period"
                            : " payment periods"
                    )
                    : ""
            ) .
            '. Reason: "' .
            $rejectionReason .
            '".';

    } else {
        $historyMessage =
            'updated pending Monetary Loan "' .
            $loanDisplayID .
            '" for member "' .
            $member["full_name"] .
            '" (' .
            $member["member_code"] .
            "). Loan Type: " .
            $loanPurpose .
            (
                $loanPurpose === "Long-Term Loan" &&
                $loanTermYears !== null
                    ? ", term " .
                    $loanTermYears .
                    (
                        $loanTermYears === 1
                            ? " year"
                            : " years"
                    ) .
                    ", " .
                    $paymentPeriods .
                    (
                        $paymentPeriods === 1
                            ? " payment period"
                            : " payment periods"
                    )
                    : ""
            ) .
            ", principal ₱" .
            number_format(
                $principalAmount,
                2
            ) .
            ", interest " .
            number_format(
                $interestRate,
                0
            ) .
            (
                $loanPurpose === "Long-Term Loan"
                    ? "% every "
                    : "% for "
            ) .
            $termDays .
            " days, and scheduled total ₱" .
            number_format(
                $scheduledTotalAmount,
                2
            ) .
            ".";
    }

    $historyLogged =
        log_activity(
            $conn,
            "Lending",
            "Updated",
            $historyMessage,
            $loanDisplayID
        );

    if (!$historyLogged) {
        error_log(
            "Unable to save Monetary Loan update history for " .
            $loanDisplayID
        );
    }


    /* =====================================================
       SUCCESS RESPONSE
    ===================================================== */

    $successMessage =
        "The pending Monetary Loan was updated.";

    if ($newStatus === "Accepted") {
        $successMessage =
            "The Monetary Loan was accepted. Interest starts today and the due date is " .
            date(
                "m-d-Y",
                strtotime($dueDate)
            ) .
            ".";
    } elseif ($newStatus === "Rejected") {
        $successMessage =
            "The Monetary Loan application was rejected and remains recorded in the ledger.";
    }

    monetaryLoanUpdateResponse(
        true,
        $successMessage,
        [
            "monetary_loan_id" =>
                $monetaryLoanID,

            "loan_display_id" =>
                $loanDisplayID,

            "status" =>
                $newStatus,

            "loan_term_years" =>
                $loanTermYears,

            "payment_periods" =>
                $loanPurpose === "Long-Term Loan"
                    ? $paymentPeriods
                    : null,

            "release_date" =>
                $newStatus === "Accepted"
                    ? $serverDate
                    : null,

            "due_date" =>
                $dueDate
        ]
    );

} catch (
    MonetaryLoanUpdateException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback($conn);
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

    monetaryLoanUpdateResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );

} catch (Throwable $exception) {
    if ($transactionStarted) {
        mysqli_rollback($conn);
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
        "Update Monetary Loan error: " .
        $exception->getMessage()
    );

    monetaryLoanUpdateResponse(
        false,
        "Unable to update the Monetary Loan. Please try again.",
        [],
        500
    );
}