<?php

/**
 * Utility functions for parsing multipart/form-data requests
 * This is especially useful for PUT requests where PHP doesn't automatically populate $_POST and $_FILES
 */

if (!function_exists('parseMultipartFormData')) {
    /**
     * Parse multipart/form-data body and populate $_POST and $_FILES arrays
     * This function extracts both form fields and file uploads from the raw request body
     * 
     * @param string $rawBody The raw request body
     * @param string $boundary The multipart boundary from Content-Type header
     * @return array Array with 'data' (form fields) and 'files' (file uploads)
     */
    function parseMultipartFormData($rawBody, $boundary) {
        $data = [];
        $files = [];
        
        if (empty($boundary) || empty($rawBody)) {
            return ['data' => $data, 'files' => $files];
        }
        
        // Split by boundary
        $parts = explode('--' . $boundary, $rawBody);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--' || $part === '-') {
                continue;
            }
            
            // Extract field name from Content-Disposition header
            if (preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)"/i', $part, $nameMatch)) {
                $fieldName = $nameMatch[1];
                
                // Check if this is a file field
                if (preg_match('/filename="([^"]*)"/i', $part, $filenameMatch)) {
                    $filename = $filenameMatch[1];
                    
                    // Extract Content-Type if present
                    $contentType = 'application/octet-stream';
                    if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $part, $contentTypeMatch)) {
                        $contentType = trim($contentTypeMatch[1]);
                    }
                    
                    // Extract file content - content comes after headers
                    if (preg_match('/\r?\n\r?\n(.*)$/s', $part, $contentMatch)) {
                        $fileContent = $contentMatch[1];
                        // Remove trailing boundary markers
                        $fileContent = rtrim($fileContent, "\r\n-");
                        
                        // Save to temporary file
                        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
                        file_put_contents($tmpFile, $fileContent);
                        
                        // Handle array notation (e.g., "galleryImages[]")
                        if (preg_match('/^(.+)\[\]$/', $fieldName, $arrayMatch)) {
                            $arrayName = $arrayMatch[1];
                            if (!isset($files[$arrayName])) {
                                $files[$arrayName] = [
                                    'name' => [],
                                    'type' => [],
                                    'tmp_name' => [],
                                    'error' => [],
                                    'size' => []
                                ];
                            }
                            $files[$arrayName]['name'][] = $filename;
                            $files[$arrayName]['type'][] = $contentType;
                            $files[$arrayName]['tmp_name'][] = $tmpFile;
                            $files[$arrayName]['error'][] = UPLOAD_ERR_OK;
                            $files[$arrayName]['size'][] = strlen($fileContent);
                        } else {
                            $files[$fieldName] = [
                                'name' => $filename,
                                'type' => $contentType,
                                'tmp_name' => $tmpFile,
                                'error' => UPLOAD_ERR_OK,
                                'size' => strlen($fileContent)
                            ];
                        }
                    }
                    continue;
                }
                
                // Regular form field
                // Extract value - content comes after the headers
                // Headers end with \r\n\r\n or \n\n
                if (preg_match('/\r?\n\r?\n(.*)$/s', $part, $valueMatch)) {
                    $value = trim($valueMatch[1]);
                    // Remove trailing boundary markers
                    $value = rtrim($value, "\r\n-");
                    
                    // Handle array notation (e.g., "galleryImages[]")
                    if (preg_match('/^(.+)\[\]$/', $fieldName, $arrayMatch)) {
                        $arrayName = $arrayMatch[1];
                        if (!isset($data[$arrayName])) {
                            $data[$arrayName] = [];
                        }
                        $data[$arrayName][] = $value;
                    } else {
                        $data[$fieldName] = $value;
                    }
                }
            }
        }
        
        return ['data' => $data, 'files' => $files];
    }
}

if (!function_exists('populateFilesFromMultipart')) {
    /**
     * Populate $_FILES array from parsed multipart files
     * This allows controllers to use $_FILES as normal
     * 
     * @param array $files Array of files in the format returned by parseMultipartFormData
     */
    function populateFilesFromMultipart($files) {
        if (empty($files)) {
            return;
        }
        
        foreach ($files as $key => $file) {
            $_FILES[$key] = $file;
        }
    }
}

if (!function_exists('saveUploadedFileOrTemp')) {
    /**
     * Save an uploaded file, handling both PHP uploaded files and manually extracted temp files
     * 
     * @param string $tmpPath Path to temporary file
     * @param string $targetPath Target path to save the file
     * @return bool True on success, false on failure
     */
    function saveUploadedFileOrTemp($tmpPath, $targetPath) {
        $success = false;
        
        // If PHP recognizes it as a genuine uploaded file, use move_uploaded_file
        if (is_uploaded_file($tmpPath)) {
            $success = move_uploaded_file($tmpPath, $targetPath);
        } else {
            // Otherwise fallback to rename/copy for manually extracted temp files
            if (!file_exists($tmpPath)) {
                error_log("saveUploadedFileOrTemp - Temp file does not exist: " . $tmpPath);
                return false;
            }

            if (@rename($tmpPath, $targetPath)) {
                $success = true;
            } elseif (@copy($tmpPath, $targetPath)) {
                // Final fallback - copy then unlink
                @unlink($tmpPath);
                $success = true;
            } else {
                error_log("saveUploadedFileOrTemp - Failed to move temp file to target: " . $targetPath);
                return false;
            }
        }
        
        // Set file permissions to 644 (readable by owner, group, and others)
        // This ensures the file is accessible via web server
        if ($success && file_exists($targetPath)) {
            @chmod($targetPath, 0644);
        }
        
        return $success;
    }
}

