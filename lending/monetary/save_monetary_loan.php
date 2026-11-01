<?php

declare(strict_types=1);

/* =========================================================
   SAVE MONETARY LOAN APPLICATION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   CUSTOM EXCEPTION
========================================================= */

class MonetaryLoanSaveException extends RuntimeException
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

function monetaryLoanSaveResponse(
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


/* =========================================================
   TEXT HELPERS
========================================================= */

function monetaryLoanNormalizeText($value): string
{
    $value =
        trim(
            (string) $value
        );

    return preg_replace(
        "/\s+/u",
        " ",
        $value
    ) ?? $value;
}


function monetaryLoanTextLength(
    string $value
): int {

    return function_exists("mb_strlen")
        ? mb_strlen(
            $value,
            "UTF-8"
        )
        : strlen($value);
}


/* =========================================================
   MONEY HELPERS
========================================================= */

function monetaryLoanAmountToCents(
    $value
): int {

    $normalized =
        str_replace(
            ",",
            "",
            trim(
                (string) $value
            )
        );

    if (
        $normalized === "" ||
        !preg_match(
            "/^\d+(?:\.\d{1,2})?$/",
            $normalized
        )
    ) {
        throw new MonetaryLoanSaveException(
            "Enter a valid principal amount with no more than two decimal places."
        );
    }

    $amount =
        (float) $normalized;

    if (
        !is_finite($amount) ||
        $amount <= 0
    ) {
        throw new MonetaryLoanSaveException(
            "Principal amount must be greater than zero."
        );
    }

    if ($amount > 9999999999.99) {
        throw new MonetaryLoanSaveException(
            "Principal amount must not exceed ₱9,999,999,999.99."
        );
    }

    return (int) round(
        $amount * 100
    );
}


function monetaryLoanCentsToAmount(
    int $cents
): float {

    return round(
        $cents / 100,
        2
    );
}


/* =========================================================
   DIRECTORY HELPERS
========================================================= */

function monetaryLoanCleanFolderName(
    string $memberCode
): string {

    $folderName =
        preg_replace(
            "/[^A-Za-z0-9_-]/",
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


function monetaryLoanEnsureDirectory(
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


function monetaryLoanCleanupFiles(
    array $movedFiles,
    array $createdDirectories
): void {

    foreach (
        $movedFiles as $filePath
    ) {
        if (is_file($filePath)) {
            @unlink($filePath);
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
}


/* =========================================================
   FILE NAME HELPERS
========================================================= */

function monetaryLoanLimitOriginalFileName(
    string $fileName
): string {

    $fileName =
        basename(
            str_replace(
                "\\",
                "/",
                $fileName
            )
        );

    $cleanedFileName =
        preg_replace(
            "/[\x00-\x1F\x7F]/",
            "",
            $fileName
        );

    if (is_string($cleanedFileName)) {
        $fileName = $cleanedFileName;
    }

    $fileName =
        trim($fileName);

    if ($fileName === "") {
        $fileName = "attachment";
    }

    if (
        function_exists("mb_substr") &&
        (
            !function_exists("mb_check_encoding") ||
            mb_check_encoding(
                $fileName,
                "UTF-8"
            )
        )
    ) {
        return mb_substr(
            $fileName,
            0,
            240,
            "UTF-8"
        );
    }

    return substr(
        $fileName,
        0,
        240
    );
}


function monetaryLoanFileExtension(
    string $fileName
): string {

    return strtolower(
        (string) pathinfo(
            basename($fileName),
            PATHINFO_EXTENSION
        )
    );
}


function monetaryLoanHasDangerousEmbeddedExtension(
    string $fileName
): bool {

    return preg_match(
        "/\.(php[0-9]*|phtml|phar|cgi|pl|py|sh|exe|com|bat|cmd|msi|js|html?|svg|sql)(?:\.|$)/i",
        basename($fileName)
    ) === 1;
}


function monetaryLoanSanitizeStoredBaseName(
    string $originalFileName
): string {

    $baseName =
        (string) pathinfo(
            basename(
                $originalFileName
            ),
            PATHINFO_FILENAME
        );

    $baseName =
    preg_replace(
        '~[\x00-\x1F\x7F<>:"/\\\\|?*]+~u',
        " ",
        $baseName
    ) ?? "";

    $baseName =
        preg_replace(
            "/\s+/u",
            " ",
            $baseName
        ) ?? "";

    $baseName =
        trim(
            $baseName,
            " .\t\n\r\0\x0B"
        );

    $reservedNames = [
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
            $reservedNames,
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


function monetaryLoanCreateStoredFileName(
    string $originalFileName,
    string $extension,
    string $directory,
    array &$reservedFileNames
): string {

    $safeBaseName =
        monetaryLoanSanitizeStoredBaseName(
            $originalFileName
        );

    $counter = 1;

    do {
        $suffix =
            $counter === 1
                ? ""
                : " (" . $counter . ")";

        $candidate =
            $safeBaseName .
            $suffix .
            "." .
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


/* =========================================================
   FILE CONTENT HELPERS
========================================================= */

function monetaryLoanFileStartsWith(
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
                strlen(
                    $expectedBytes
                )
            );
    } finally {
        fclose($handle);
    }

    return
        is_string($actualBytes) &&
        hash_equals(
            $expectedBytes,
            $actualBytes
        );
}


function monetaryLoanIsValidLegacyDoc(
    string $filePath
): bool {

    if (
        !monetaryLoanFileStartsWith(
            $filePath,
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
        )
    ) {
        return false;
    }

    $contents =
        @file_get_contents(
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


function monetaryLoanIsValidDocx(
    string $filePath
): bool {

    if (!class_exists("ZipArchive")) {
        throw new RuntimeException(
            "The PHP ZIP extension is unavailable."
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
        $requiredEntries = [
            "[Content_Types].xml",
            "_rels/.rels",
            "word/document.xml"
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
                "[Content_Types].xml"
            );

        if (
            !is_array($contentTypeStat) ||
            (int) (
                $contentTypeStat["size"]
                ?? 0
            ) <= 0 ||
            (int) (
                $contentTypeStat["size"]
                ?? 0
            ) > 1024 * 1024
        ) {
            return false;
        }

        $contentTypes =
            $archive->getFromName(
                "[Content_Types].xml"
            );

        return
            is_string($contentTypes) &&
            stripos(
                $contentTypes,
                "wordprocessingml.document.main+xml"
            ) !== false;
    } finally {
        $archive->close();
    }
}


/* =========================================================
   NORMALIZE UPLOADS
========================================================= */

function monetaryLoanNormalizeUploads(
    string $fieldName
): array {

    if (
        !isset(
            $_FILES[$fieldName]
        ) ||
        !isset(
            $_FILES[$fieldName]["name"]
        )
    ) {
        return [];
    }

    $files =
        $_FILES[$fieldName];

    $normalized = [];

    if (
        is_array(
            $files["name"]
        )
    ) {
        $count =
            count(
                $files["name"]
            );

        for (
            $index = 0;
            $index < $count;
            $index++
        ) {
            $error =
                isset(
                    $files["error"][$index]
                )
                    ? (int)
                      $files["error"][$index]
                    : UPLOAD_ERR_NO_FILE;

            if (
                $error ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $normalized[] = [
                "name" =>
                    $files["name"][$index]
                    ?? "",

                "tmp_name" =>
                    $files["tmp_name"][$index]
                    ?? "",

                "error" =>
                    $error,

                "size" =>
                    isset(
                        $files["size"][$index]
                    )
                        ? (int)
                          $files["size"][$index]
                        : 0
            ];
        }

        return $normalized;
    }

    $error =
        isset(
            $files["error"]
        )
            ? (int) $files["error"]
            : UPLOAD_ERR_NO_FILE;

    if (
        $error !==
        UPLOAD_ERR_NO_FILE
    ) {
        $normalized[] = [
            "name" =>
                $files["name"]
                ?? "",

            "tmp_name" =>
                $files["tmp_name"]
                ?? "",

            "error" =>
                $error,

            "size" =>
                isset(
                    $files["size"]
                )
                    ? (int) $files["size"]
                    : 0
        ];
    }

    return $normalized;
}


/* =========================================================
   UPLOAD ERROR MESSAGE
========================================================= */

function monetaryLoanUploadErrorMessage(
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
                "A server extension stopped an attachment upload.";

        default:

            return
                "An attachment could not be uploaded.";
    }
}


/* =========================================================
   VALIDATE UPLOADS
========================================================= */

function monetaryLoanValidateUploads(
    array $files
): array {

    $maximumAttachments = 10;

    $maximumFileSize =
        10 * 1024 * 1024;

    $maximumCombinedSize =
        50 * 1024 * 1024;

    $allowedExtensions = [
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
        throw new MonetaryLoanSaveException(
            "A maximum of 10 attachments is allowed per application."
        );
    }

    if (count($files) === 0) {
        throw new MonetaryLoanSaveException(
            "At least one supporting document is required before saving the Monetary Loan application."
        );
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
            "Unable to inspect uploaded files."
        );
    }

    $validatedFiles = [];

    $totalSize = 0;

    $uploadHashes = [];

    try {
        foreach (
            $files as $file
        ) {
            $uploadError =
                (int) (
                    $file["error"]
                    ?? UPLOAD_ERR_NO_FILE
                );

            if (
                $uploadError !==
                UPLOAD_ERR_OK
            ) {
                throw new MonetaryLoanSaveException(
                    monetaryLoanUploadErrorMessage(
                        $uploadError
                    )
                );
            }

            $fileSize =
                (int) (
                    $file["size"]
                    ?? 0
                );

            if ($fileSize <= 0) {
                throw new MonetaryLoanSaveException(
                    "An attachment is empty."
                );
            }

            if (
                $fileSize >
                $maximumFileSize
            ) {
                throw new MonetaryLoanSaveException(
                    "Each attachment must not exceed 10 MB."
                );
            }

            $totalSize +=
                $fileSize;

            if (
                $totalSize >
                $maximumCombinedSize
            ) {
                throw new MonetaryLoanSaveException(
                    "All attachments must not exceed 50 MB in total."
                );
            }

            $temporaryPath =
                (string) (
                    $file["tmp_name"]
                    ?? ""
                );

            $rawOriginalName =
                basename(
                    str_replace(
                        "\\",
                        "/",
                        (string) (
                            $file["name"]
                            ?? ""
                        )
                    )
                );

            $originalExtension =
                monetaryLoanFileExtension(
                    $rawOriginalName
                );

            $originalName =
                monetaryLoanLimitOriginalFileName(
                    $rawOriginalName
                );

            if (
                !in_array(
                    $originalExtension,
                    $allowedExtensions,
                    true
                )
            ) {
                error_log(
                    "Rejected Monetary Loan attachment: filename=" .
                    json_encode($rawOriginalName) .
                    ", extension=" .
                    json_encode($originalExtension)
                );

                throw new MonetaryLoanSaveException(
                    "The attachment \"" .
                    ($originalName !== "" ? $originalName : "unnamed file") .
                    "\" does not have an allowed file extension. " .
                    "Only JPG, PNG, PDF, DOC, and DOCX are allowed."
                );
            }

            if (
                monetaryLoanHasDangerousEmbeddedExtension(
                    $originalName
                )
            ) {
                throw new MonetaryLoanSaveException(
                    "An unsafe attachment filename was detected."
                );
            }

            if (
                !is_uploaded_file(
                    $temporaryPath
                )
            ) {
                throw new MonetaryLoanSaveException(
                    "An invalid attachment upload was detected.",
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
                throw new MonetaryLoanSaveException(
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
                        $detectedMimeType !==
                            "image/jpeg" ||
                        !is_array(
                            $imageDetails
                        ) ||
                        (
                            $imageDetails["mime"]
                            ?? ""
                        ) !== "image/jpeg"
                    ) {
                        throw new MonetaryLoanSaveException(
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
                        $detectedMimeType !==
                            "image/png" ||
                        !is_array(
                            $imageDetails
                        ) ||
                        (
                            $imageDetails["mime"]
                            ?? ""
                        ) !== "image/png"
                    ) {
                        throw new MonetaryLoanSaveException(
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
                            $detectedMimeType,
                            [
                                "application/pdf",
                                "application/x-pdf"
                            ],
                            true
                        ) ||
                        !monetaryLoanFileStartsWith(
                            $temporaryPath,
                            "%PDF-"
                        )
                    ) {
                        throw new MonetaryLoanSaveException(
                            "A PDF attachment does not contain a valid PDF document."
                        );
                    }

                    $storedExtension =
                        "pdf";

                    $canonicalMimeType =
                        "application/pdf";

                    break;

                case "doc":

                    if (
                        !in_array(
                            $detectedMimeType,
                            [
                                "application/msword",
                                "application/x-ole-storage",
                                "application/CDFV2",
                                "application/octet-stream"
                            ],
                            true
                        ) ||
                        !monetaryLoanIsValidLegacyDoc(
                            $temporaryPath
                        )
                    ) {
                        throw new MonetaryLoanSaveException(
                            "A DOC attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension =
                        "doc";

                    $canonicalMimeType =
                        "application/msword";

                    break;

                case "docx":

                    if (
                        !in_array(
                            $detectedMimeType,
                            [
                                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                "application/zip",
                                "application/octet-stream"
                            ],
                            true
                        ) ||
                        !monetaryLoanFileStartsWith(
                            $temporaryPath,
                            "PK"
                        ) ||
                        !monetaryLoanIsValidDocx(
                            $temporaryPath
                        )
                    ) {
                        throw new MonetaryLoanSaveException(
                            "A DOCX attachment does not contain a valid Word document."
                        );
                    }

                    $storedExtension =
                        "docx";

                    $canonicalMimeType =
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

                    break;

                default:

                    throw new MonetaryLoanSaveException(
                        "The attachment type is not allowed."
                    );
            }

            $fileHash =
                hash_file(
                    "sha256",
                    $temporaryPath
                );

            if (
                !is_string($fileHash) ||
                $fileHash === ""
            ) {
                throw new RuntimeException(
                    "Unable to verify an attachment."
                );
            }

            if (
                isset(
                    $uploadHashes[
                        $fileHash
                    ]
                )
            ) {
                throw new MonetaryLoanSaveException(
                    "This file is already attached: " .
                    $originalName
                );
            }

            $uploadHashes[
                $fileHash
            ] = true;

            $validatedFiles[] = [
                "original_name" =>
                    $originalName,

                "tmp_name" =>
                    $temporaryPath,

                "mime_type" =>
                    $canonicalMimeType,

                "extension" =>
                    $storedExtension,

                "file_size" =>
                    $fileSize,

                "file_hash" =>
                    $fileHash
            ];
        }
    } finally {
        finfo_close(
            $fileInfo
        );
    }

    return $validatedFiles;
}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    strtoupper(
        (string) (
            $_SERVER["REQUEST_METHOD"]
            ?? ""
        )
    ) !== "POST"
) {
    monetaryLoanSaveResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}

if (
    strtolower(
        (string) (
            $_SERVER[
                "HTTP_X_REQUESTED_WITH"
            ]
            ?? ""
        )
    ) !== "xmlhttprequest"
) {
    monetaryLoanSaveResponse(
        false,
        "Invalid request.",
        [],
        400
    );
}


/* =========================================================
   SESSION AND PERMISSION
========================================================= */

$userID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int)
          $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {
    monetaryLoanSaveResponse(
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
    monetaryLoanSaveResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

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
    monetaryLoanSaveResponse(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        [],
        419
    );
}


/* =========================================================
   FORM VALUES AND COMPUTATION
========================================================= */

try {
    $memberID =
        isset(
            $_POST["member_id"]
        )
            ? (int)
              $_POST["member_id"]
            : 0;

    $loanPurpose =
        monetaryLoanNormalizeText(
            $_POST["loan_purpose"]
            ?? ""
        );

    $termDays =
        isset(
            $_POST["loan_term"]
        )
            ? (int)
            $_POST["loan_term"]
            : 0;

    $loanTermYearsRaw =
        trim(
            (string) (
                $_POST["loan_term_years"]
                ?? ""
            )
        );

    $loanTermYears = null;

    if ($memberID <= 0) {
        throw new MonetaryLoanSaveException(
            "Select an eligible Regular member."
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
        throw new MonetaryLoanSaveException(
            "Select a valid Monetary Loan type."
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
            throw new MonetaryLoanSaveException(
                "Enter a valid whole number of years for the Long-Term Loan."
            );
        }

        $loanTermYears =
            (int) $loanTermYearsRaw;

        if (
            $loanTermYears < 1 ||
            $loanTermYears > 65535
        ) {
            throw new MonetaryLoanSaveException(
                "Enter a valid whole number of years for the Long-Term Loan."
            );
        }
    }

    $allowedTerms = [
        180 => 10,
        360 => 20
    ];

    if (
        !isset(
            $allowedTerms[
                $termDays
            ]
        )
    ) {
        throw new MonetaryLoanSaveException(
            "Select a valid loan term."
        );
    }

    $interestRate =
        $allowedTerms[
            $termDays
        ];

    $principalCents =
        monetaryLoanAmountToCents(
            $_POST["principal_amount"]
            ?? ""
        );

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
        * Diminishing balance:
        *
        * Equal principal installments.
        * Interest is based on the remaining
        * principal before each installment.
        *
        * Total interest:
        * Principal × Rate × (Periods + 1) / 2
        */
        $scheduledInterestCents =
            (int) round(
                $principalCents *
                ($interestRate / 100) *
                (($paymentPeriods + 1) / 2)
            );
    } else {
        $paymentPeriods = 1;

        $scheduledInterestCents =
            (int) round(
                $principalCents *
                ($interestRate / 100)
            );
    }

    $scheduledTotalCents =
        $principalCents +
        $scheduledInterestCents;

    $maximumStoredAmountCents =
        999999999999999;

    if (
        $scheduledInterestCents >
            $maximumStoredAmountCents ||
        $scheduledTotalCents >
            $maximumStoredAmountCents
    ) {
        throw new MonetaryLoanSaveException(
            "The computed Long-Term Loan amount exceeds the system limit. Reduce the principal amount or loan term."
        );
    }

    $principalAmount =
        monetaryLoanCentsToAmount(
            $principalCents
        );

    $scheduledInterestAmount =
        monetaryLoanCentsToAmount(
            $scheduledInterestCents
        );

    $scheduledTotalAmount =
        monetaryLoanCentsToAmount(
            $scheduledTotalCents
        );

    $uploadedFiles =
        monetaryLoanValidateUploads(
            monetaryLoanNormalizeUploads(
                "attachments"
            )
        );
} catch (
    MonetaryLoanSaveException $exception
) {
    monetaryLoanSaveResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );
} catch (Throwable $exception) {
    error_log(
        "Monetary Loan validation error: " .
        $exception->getMessage()
    );

    monetaryLoanSaveResponse(
        false,
        "Unable to validate the Monetary Loan application.",
        [],
        500
    );
}


/* =========================================================
   DATABASE AND HISTORY LOGGER
========================================================= */

require_once "../../db.php";

require_once
    "../../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanSaveResponse(
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


/* =========================================================
   DATABASE TRANSACTION
========================================================= */

$transactionStarted = false;

$movedFilePaths = [];

$createdDirectories = [];

try {
    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY CURRENT ACCOUNT
    ----------------------------------------------------- */

    $userStatement =
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

    if (!$userStatement) {
        throw new RuntimeException(
            "Unable to prepare account validation."
        );
    }

    mysqli_stmt_bind_param(
        $userStatement,
        "i",
        $userID
    );

    mysqli_stmt_execute(
        $userStatement
    );

    $userResult =
        mysqli_stmt_get_result(
            $userStatement
        );

    $currentUser =
        mysqli_fetch_assoc(
            $userResult
        );

    mysqli_stmt_close(
        $userStatement
    );

    if (!$currentUser) {
        throw new MonetaryLoanSaveException(
            "The logged-in account could not be found.",
            401
        );
    }


    /* -----------------------------------------------------
    VERIFY SELECTED MEMBER

    Monetary Loan eligibility:
    - Registered member
    - Not archived
    - Active
    - Regular classification

    Classification from the database is the
    final authority. Current CBU balance is not
    used to determine Monetary Loan eligibility.
    ----------------------------------------------------- */

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
        throw new MonetaryLoanSaveException(
            "The selected member could not be found.",
            404
        );
    }


    /* -----------------------------------------------------
    ACTIVE MEMBER
    ----------------------------------------------------- */

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
        throw new MonetaryLoanSaveException(
            "Only active members may apply for a Monetary Loan.",
            409
        );
    }


    /* -----------------------------------------------------
    REGULAR MEMBER

    Important:
    Eligibility is based on the stored permanent
    classification, not the member's current CBU balance.
    ----------------------------------------------------- */

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
        throw new MonetaryLoanSaveException(
            "Only Regular members are eligible to apply for a Monetary Loan.",
            422
        );
    }


    /* -----------------------------------------------------
    SAME MONETARY LOAN TYPE RESTRICTION

    A member may not create another loan of the same
    Monetary Loan type while an existing loan of that type
    is still Pending or has an unpaid Accepted balance.

    Other Monetary Loan types remain available.
    ----------------------------------------------------- */

    $activeSameTypeLoanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                monetary_loan_id,
                status

            FROM monetary_loans

            WHERE
                member_id = ?
                AND is_archived = 0
                AND loan_purpose = ?

                AND (
                    status = 'Pending'

                    OR

                    (
                        status = 'Accepted'

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
                monetary_loan_id DESC

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
        "is",
        $memberID,
        $loanPurpose
    );

    mysqli_stmt_execute(
        $activeSameTypeLoanStatement
    );

    $activeSameTypeLoanResult =
        mysqli_stmt_get_result(
            $activeSameTypeLoanStatement
        );

    $activeSameTypeLoan =
        mysqli_fetch_assoc(
            $activeSameTypeLoanResult
        );

    mysqli_stmt_close(
        $activeSameTypeLoanStatement
    );

    if ($activeSameTypeLoan) {
        throw new MonetaryLoanSaveException(
            $member["full_name"] .
            " still has an active " .
            $loanPurpose .
            ". The existing " .
            $loanPurpose .
            " must be fully paid before another one can be created.",
            409
        );
    }


    /* -----------------------------------------------------
       INSERT PENDING MONETARY LOAN
    ----------------------------------------------------- */

    $applicationDate =
        date("Y-m-d");

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO monetary_loans (
                member_id,
                application_date,
                loan_purpose,
                interest_rate,
                term_days,
                loan_term_years,
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
                ?,
                ?,
                'Pending',
                ?
            )
            "
        );

    if (!$loanStatement) {
        throw new RuntimeException(
            "Unable to prepare the Monetary Loan record."
        );
    }

    mysqli_stmt_bind_param(
        $loanStatement,
        "issdiiddddi",
        $memberID,
        $applicationDate,
        $loanPurpose,
        $interestRate,
        $termDays,
        $loanTermYears,
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
            "Unable to create the Monetary Loan record."
        );
    }

    $monetaryLoanID =
        (int) mysqli_insert_id(
            $conn
        );

    mysqli_stmt_close(
        $loanStatement
    );

    $loanDisplayID =
        "ML-" .
        str_pad(
            (string) $monetaryLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );


    /* -----------------------------------------------------
       SAVE ATTACHMENTS
    ----------------------------------------------------- */

    if (
        count(
            $uploadedFiles
        ) > 0
    ) {
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
            "monetary";

        monetaryLoanEnsureDirectory(
            $attachmentBaseDirectory,
            "Unable to create the Monetary Loan document folder."
        );

        $securityFilePath =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            ".htaccess";

        if (
            !is_file(
                $securityFilePath
            )
        ) {
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
                    "Unable to secure the Monetary Loan document folder."
                );
            }
        }

        $memberFolderName =
            monetaryLoanCleanFolderName(
                (string)
                $member["member_code"]
            );

        $memberDirectory =
            $attachmentBaseDirectory .
            DIRECTORY_SEPARATOR .
            $memberFolderName;

        $memberDirectoryExisted =
            is_dir(
                $memberDirectory
            );

        monetaryLoanEnsureDirectory(
            $memberDirectory,
            "Unable to create the member's Monetary Loan document folder."
        );

        if (!$memberDirectoryExisted) {
            $createdDirectories[] =
                $memberDirectory;
        }

        $attachmentDirectory =
            $memberDirectory .
            DIRECTORY_SEPARATOR .
            $loanDisplayID;

        $attachmentDirectoryExisted =
            is_dir(
                $attachmentDirectory
            );

        monetaryLoanEnsureDirectory(
            $attachmentDirectory,
            "Unable to create the Monetary Loan attachment folder."
        );

        if (!$attachmentDirectoryExisted) {
            $createdDirectories[] =
                $attachmentDirectory;
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
                "Unable to prepare Monetary Loan attachments."
            );
        }

        $reservedStoredFileNames = [];

        foreach (
            $uploadedFiles as $file
        ) {
            $storedFileName =
                monetaryLoanCreateStoredFileName(
                    $file["original_name"],
                    $file["extension"],
                    $attachmentDirectory,
                    $reservedStoredFileNames
                );

            $absoluteFilePath =
                $attachmentDirectory .
                DIRECTORY_SEPARATOR .
                $storedFileName;

            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $absoluteFilePath
                )
            ) {
                throw new RuntimeException(
                    "Unable to store an attachment."
                );
            }

            $movedFilePaths[] =
                $absoluteFilePath;

            $storedHash =
                hash_file(
                    "sha256",
                    $absoluteFilePath
                );

            if (
                !is_string($storedHash) ||
                !hash_equals(
                    $file["file_hash"],
                    $storedHash
                )
            ) {
                throw new RuntimeException(
                    "Unable to verify an attachment after storage."
                );
            }

            $relativeFilePath =
                "assets/documents/lending/monetary/" .
                $memberFolderName .
                "/" .
                $loanDisplayID .
                "/" .
                $storedFileName;

            $originalFileName =
                $file["original_name"];

            $mimeType =
                $file["mime_type"];

            $fileSize =
                (int)
                $file["file_size"];

            $fileHash =
                $storedHash;

            mysqli_stmt_bind_param(
                $attachmentStatement,
                "issssisi",
                $monetaryLoanID,
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
                    "Unable to record an attachment."
                );
            }
        }

        mysqli_stmt_close(
            $attachmentStatement
        );
    }


    /* -----------------------------------------------------
       INITIAL PENDING STATUS HISTORY
    ----------------------------------------------------- */

    $statusReason =
        "Monetary Loan application created.";

    $statusStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO monetary_loan_status_history (
                monetary_loan_id,
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
            "Unable to prepare the initial status history."
        );
    }

    mysqli_stmt_bind_param(
        $statusStatement,
        "iis",
        $monetaryLoanID,
        $userID,
        $statusReason
    );

    if (
        !mysqli_stmt_execute(
            $statusStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to save the initial status history."
        );
    }

    mysqli_stmt_close(
        $statusStatement
    );


    /* -----------------------------------------------------
       CENTRAL HISTORY LOG
    ----------------------------------------------------- */

    $historyMessage =
    "Created Monetary Loan \"" .
    $loanDisplayID .
    "\" for member \"" .
    $member["full_name"] .
    "\" (" .
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
    ". Principal ₱" .
        number_format(
            $principalAmount,
            2
        ) .
        ", interest " .
        number_format(
            (float) $interestRate,
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
        ", status Pending, and " .
        count(
            $uploadedFiles
        ) .
        " attachment(s).";

    $historyLogged =
        log_activity(
            $conn,
            "Lending",
            "Added",
            $historyMessage,
            $loanDisplayID
        );

    if (!$historyLogged) {
        error_log(
            "Unable to save Monetary Loan create history for " .
            $loanDisplayID
        );
    }


    /* -----------------------------------------------------
       COMMIT
    ----------------------------------------------------- */

    mysqli_commit(
        $conn
    );

    $transactionStarted = false;


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    monetaryLoanSaveResponse(
        true,
        "Monetary Loan " .
        $loanDisplayID .
        " for " .
        $member["full_name"] .
        " was saved as Pending.",
        [
            "monetary_loan_id" =>
                $monetaryLoanID,

            "loan_display_id" =>
                $loanDisplayID,

            "status" =>
                "Pending",

            "application_date" =>
                $applicationDate,

            "loan_term_years" =>
                $loanTermYears,

            "payment_periods" =>
                $loanPurpose === "Long-Term Loan"
                    ? $paymentPeriods
                    : null,

            "principal_amount" =>
                number_format(
                    $principalAmount,
                    2,
                    ".",
                    ""
                ),

            "scheduled_interest_amount" =>
                number_format(
                    $scheduledInterestAmount,
                    2,
                    ".",
                    ""
                ),

            "scheduled_total_amount" =>
                number_format(
                    $scheduledTotalAmount,
                    2,
                    ".",
                    ""
                )
        ],
        201
    );
} catch (
    MonetaryLoanSaveException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    monetaryLoanCleanupFiles(
        $movedFilePaths,
        $createdDirectories
    );

    monetaryLoanSaveResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );
} catch (Throwable $exception) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    monetaryLoanCleanupFiles(
        $movedFilePaths,
        $createdDirectories
    );

    error_log(
        "Save Monetary Loan error: " .
        $exception->getMessage()
    );

    monetaryLoanSaveResponse(
        false,
        "Unable to save the Monetary Loan application. Please try again.",
        [],
        500
    );
}