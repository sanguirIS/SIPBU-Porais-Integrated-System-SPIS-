<?php

/* =========================================================
   VIEW MONETARY LOAN ATTACHMENT
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

ini_set("display_errors", "0");
ini_set("zlib.output_compression", "0");

error_reporting(E_ALL);


/* =========================================================
   ERROR RESPONSE
========================================================= */

function monetaryLoanAttachmentError(
    string $message,
    int $statusCode = 400
): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);

    header(
        "Content-Type: text/html; charset=utf-8"
    );

    header(
        "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
    );

    $safeMessage =
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            "UTF-8"
        );

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Attachment unavailable</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f2cf;
            color: #242722;
            font-family: Arial, sans-serif;
        }

        .attachment-error {
            width: min(480px, 100%);
            padding: 34px;
            text-align: center;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(24, 31, 18, 0.18);
        }

        .attachment-error-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff0f0;
            color: #db4f4f;
            font-size: 40px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        p {
            margin: 0;
            color: #666666;
            font-size: 17px;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <main class="attachment-error">
        <div class="attachment-error-icon">×</div>

        <h1>Attachment unavailable</h1>

        <p>{$safeMessage}</p>
    </main>
</body>
</html>
HTML;

    exit;
}


/* =========================================================
   FILE SIGNATURE HELPER
========================================================= */

function monetaryLoanAttachmentStartsWith(
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

    $actualBytes =
        fread(
            $handle,
            strlen($expectedBytes)
        );

    fclose($handle);

    return
        is_string($actualBytes) &&
        hash_equals(
            $expectedBytes,
            $actualBytes
        );
}


/* =========================================================
   LEGACY DOC VALIDATION
========================================================= */

function monetaryLoanAttachmentIsValidDoc(
    string $filePath
): bool {
    $oleSignature =
        "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    if (
        !monetaryLoanAttachmentStartsWith(
            $filePath,
            $oleSignature
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
        "W\x00o\x00r\x00d\x00" .
        "D\x00o\x00c\x00u\x00m\x00e\x00n\x00t\x00";

    return
        strpos(
            $contents,
            $wordDocumentStream
        ) !== false;
}


/* =========================================================
   DOCX VALIDATION
========================================================= */

function monetaryLoanAttachmentIsValidDocx(
    string $filePath
): bool {
    if (!class_exists("ZipArchive")) {
        return false;
    }

    if (
        !monetaryLoanAttachmentStartsWith(
            $filePath,
            "PK"
        )
    ) {
        return false;
    }

    $archive = new ZipArchive();

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
            $requiredEntries
            as $entryName
        ) {
            if (
                $archive->locateName(
                    $entryName
                ) === false
            ) {
                return false;
            }
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
   TRUE FILE TYPE VALIDATION
========================================================= */

function monetaryLoanAttachmentValidateContent(
    string $filePath,
    string $extension
): ?string {
    $fileInfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );

    if ($fileInfo === false) {
        return null;
    }

    $detectedMimeType =
        finfo_file(
            $fileInfo,
            $filePath
        );

    finfo_close($fileInfo);

    if (!is_string($detectedMimeType)) {
        return null;
    }

    switch ($extension) {
        case "jpg":
        case "jpeg":
            $imageDetails =
                @getimagesize(
                    $filePath
                );

            if (
                $detectedMimeType !== "image/jpeg" ||
                !is_array($imageDetails) ||
                (
                    $imageDetails["mime"]
                    ?? ""
                ) !== "image/jpeg"
            ) {
                return null;
            }

            return "image/jpeg";

        case "png":
            $imageDetails =
                @getimagesize(
                    $filePath
                );

            if (
                $detectedMimeType !== "image/png" ||
                !is_array($imageDetails) ||
                (
                    $imageDetails["mime"]
                    ?? ""
                ) !== "image/png"
            ) {
                return null;
            }

            return "image/png";

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
                !monetaryLoanAttachmentStartsWith(
                    $filePath,
                    "%PDF-"
                )
            ) {
                return null;
            }

            return "application/pdf";

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
                !monetaryLoanAttachmentIsValidDoc(
                    $filePath
                )
            ) {
                return null;
            }

            return "application/msword";

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
                !monetaryLoanAttachmentIsValidDocx(
                    $filePath
                )
            ) {
                return null;
            }

            return
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

        default:
            return null;
    }
}


/* =========================================================
   REQUEST METHOD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "GET"
) {
    monetaryLoanAttachmentError(
        "Invalid request method.",
        405
    );
}


/* =========================================================
   SESSION VALIDATION
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {
    monetaryLoanAttachmentError(
        "Your session has expired. Please log in again.",
        401
    );
}


/* =========================================================
   ATTACHMENT ID
========================================================= */

$attachmentID =
    isset($_GET["attachment_id"])
        ? filter_var(
            $_GET["attachment_id"],
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        )
        : false;

if ($attachmentID === false) {
    monetaryLoanAttachmentError(
        "Invalid Monetary Loan attachment.",
        422
    );
}

$attachmentID =
    (int) $attachmentID;


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanAttachmentError(
        "Database connection is unavailable.",
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
   VERIFY CURRENT USER
========================================================= */

$userStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT user_id

        FROM users

        WHERE user_id = ?

        LIMIT 1
        "
    );

if (!$userStatement) {
    monetaryLoanAttachmentError(
        "Unable to verify the current account.",
        500
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
    monetaryLoanAttachmentError(
        "The logged-in account could not be found.",
        401
    );
}


/* =========================================================
   RETRIEVE ATTACHMENT
========================================================= */

$attachmentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT
            attachment.monetary_loan_attachment_id,
            attachment.monetary_loan_id,
            attachment.original_file_name,
            attachment.stored_file_name,
            attachment.file_path,
            attachment.mime_type,
            attachment.file_size,
            attachment.file_hash,
            loan.is_archived AS loan_is_archived

        FROM monetary_loan_attachments AS attachment

        INNER JOIN monetary_loans AS loan
            ON loan.monetary_loan_id =
               attachment.monetary_loan_id

        WHERE
            attachment.monetary_loan_attachment_id = ?
            AND attachment.is_removed = 0

        LIMIT 1
        "
    );

if (!$attachmentStatement) {
    monetaryLoanAttachmentError(
        "Unable to retrieve the attachment.",
        500
    );
}

mysqli_stmt_bind_param(
    $attachmentStatement,
    "i",
    $attachmentID
);

mysqli_stmt_execute(
    $attachmentStatement
);

$attachmentResult =
    mysqli_stmt_get_result(
        $attachmentStatement
    );

$attachment =
    mysqli_fetch_assoc(
        $attachmentResult
    );

mysqli_stmt_close(
    $attachmentStatement
);

if (!$attachment) {
    monetaryLoanAttachmentError(
        "The attachment does not exist or has already been removed.",
        404
    );
}

$loanIsArchived =
    (int) (
        $attachment["loan_is_archived"]
        ?? 0
    );


if ($loanIsArchived === 1) {

    if (
        (int) (
            $_SESSION["archives_access"]
            ?? 0
        ) !== 1
    ) {
        monetaryLoanAttachmentError(
            "You do not have permission to access archived Monetary Loan attachments.",
            403
        );
    }

}
else {

    if (
        (int) (
            $_SESSION["lending_access"]
            ?? 0
        ) !== 1
    ) {
        monetaryLoanAttachmentError(
            "You do not have permission to access Monetary Loan attachments.",
            403
        );
    }

}


/* =========================================================
   DATABASE FILE INFORMATION
========================================================= */

$originalFileName =
    trim(
        (string) (
            $attachment["original_file_name"]
            ?? ""
        )
    );

$storedFileName =
    trim(
        (string) (
            $attachment["stored_file_name"]
            ?? ""
        )
    );

$relativeFilePath =
    trim(
        (string) (
            $attachment["file_path"]
            ?? ""
        )
    );

$databaseFileHash =
    strtolower(
        trim(
            (string) (
                $attachment["file_hash"]
                ?? ""
            )
        )
    );

if (
    $originalFileName === "" ||
    $storedFileName === "" ||
    $relativeFilePath === ""
) {
    monetaryLoanAttachmentError(
        "The attachment record is incomplete.",
        500
    );
}

if (
    strpos($originalFileName, "\0") !== false ||
    strpos($storedFileName, "\0") !== false ||
    strpos($relativeFilePath, "\0") !== false
) {
    monetaryLoanAttachmentError(
        "The attachment path is invalid.",
        400
    );
}


/* =========================================================
   ALLOWED STORAGE PATH
========================================================= */

$normalizedRelativePath =
    str_replace(
        "\\",
        "/",
        $relativeFilePath
    );

$requiredPathPrefix =
    "assets/documents/lending/monetary/";

if (
    strpos(
        $normalizedRelativePath,
        $requiredPathPrefix
    ) !== 0
) {
    monetaryLoanAttachmentError(
        "The attachment is outside the allowed Monetary Loan folder.",
        403
    );
}

if (
    strpos(
        $normalizedRelativePath,
        "../"
    ) !== false
) {
    monetaryLoanAttachmentError(
        "The attachment path is invalid.",
        403
    );
}

if (
    basename(
        $normalizedRelativePath
    ) !== $storedFileName
) {
    monetaryLoanAttachmentError(
        "The stored attachment name does not match its path.",
        500
    );
}


/* =========================================================
   PHYSICAL FILE PATH
========================================================= */

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

$realAttachmentBaseDirectory =
    realpath(
        $attachmentBaseDirectory
    );

if ($realAttachmentBaseDirectory === false) {
    monetaryLoanAttachmentError(
        "The Monetary Loan attachment folder could not be found.",
        404
    );
}

$absoluteFilePath =
    $systemRoot .
    DIRECTORY_SEPARATOR .
    str_replace(
        "/",
        DIRECTORY_SEPARATOR,
        $normalizedRelativePath
    );

$realFilePath =
    realpath(
        $absoluteFilePath
    );

if (
    $realFilePath === false ||
    !is_file($realFilePath) ||
    !is_readable($realFilePath)
) {
    monetaryLoanAttachmentError(
        "The physical attachment file could not be found.",
        404
    );
}

$allowedDirectoryPrefix =
    rtrim(
        $realAttachmentBaseDirectory,
        DIRECTORY_SEPARATOR
    ) .
    DIRECTORY_SEPARATOR;

if (
    strncasecmp(
        $realFilePath,
        $allowedDirectoryPrefix,
        strlen($allowedDirectoryPrefix)
    ) !== 0
) {
    monetaryLoanAttachmentError(
        "Access to this file path is not allowed.",
        403
    );
}


/* =========================================================
   FILE SIZE AND HASH
========================================================= */

$actualFileSize =
    filesize(
        $realFilePath
    );

if (
    $actualFileSize === false ||
    $actualFileSize <= 0
) {
    monetaryLoanAttachmentError(
        "The attachment is empty or unreadable.",
        404
    );
}

if (
    $actualFileSize >
        10 * 1024 * 1024
) {
    monetaryLoanAttachmentError(
        "The attachment exceeds the allowed file size.",
        403
    );
}

if (
    preg_match(
        "/^[a-f0-9]{64}$/",
        $databaseFileHash
    ) === 1
) {
    $actualFileHash =
        hash_file(
            "sha256",
            $realFilePath
        );

    if (
        !is_string($actualFileHash) ||
        !hash_equals(
            $databaseFileHash,
            strtolower($actualFileHash)
        )
    ) {
        monetaryLoanAttachmentError(
            "The attachment failed its security verification.",
            403
        );
    }
}


/* =========================================================
   TRUE FILE TYPE
========================================================= */

$extension =
    strtolower(
        (string) pathinfo(
            $storedFileName,
            PATHINFO_EXTENSION
        )
    );

$canonicalMimeType =
    monetaryLoanAttachmentValidateContent(
        $realFilePath,
        $extension
    );

if ($canonicalMimeType === null) {
    monetaryLoanAttachmentError(
        "The attachment content is invalid or unsupported.",
        403
    );
}


/* =========================================================
   SAFE DOWNLOAD NAME
========================================================= */

$safeDownloadName =
    basename(
        str_replace(
            [
                "\r",
                "\n",
                "\0",
                "\"",
                "\\"
            ],
            "_",
            $originalFileName
        )
    );

if ($safeDownloadName === "") {
    $safeDownloadName =
        $storedFileName;
}

$asciiDownloadName =
    preg_replace(
        "/[^\x20-\x7E]/",
        "_",
        $safeDownloadName
    );

if (
    !is_string($asciiDownloadName) ||
    trim($asciiDownloadName) === ""
) {
    $asciiDownloadName =
        "attachment." .
        $extension;
}

$encodedDownloadName =
    rawurlencode(
        $safeDownloadName
    );


/* =========================================================
   PREVIEW OR DOWNLOAD
========================================================= */

$inlineExtensions = [
    "jpg",
    "jpeg",
    "png",
    "pdf"
];

$contentDisposition =
    in_array(
        $extension,
        $inlineExtensions,
        true
    )
        ? "inline"
        : "attachment";


/* =========================================================
   OPEN FILE
========================================================= */

$fileHandle =
    @fopen(
        $realFilePath,
        "rb"
    );

if (!$fileHandle) {
    monetaryLoanAttachmentError(
        "The attachment could not be opened.",
        500
    );
}


/* =========================================================
   SEND FILE
========================================================= */

while (ob_get_level() > 0) {
    ob_end_clean();
}

header(
    "Content-Type: " .
    $canonicalMimeType
);

header(
    "Content-Length: " .
    (string) $actualFileSize
);

header(
    "Content-Disposition: " .
    $contentDisposition .
    "; filename=\"" .
    $asciiDownloadName .
    "\"; filename*=UTF-8''" .
    $encodedDownloadName
);

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "X-Frame-Options: SAMEORIGIN"
);

header(
    "Cross-Origin-Resource-Policy: same-origin"
);

header(
    "Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0"
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);

mysqli_close($conn);

fpassthru($fileHandle);

fclose($fileHandle);

exit;