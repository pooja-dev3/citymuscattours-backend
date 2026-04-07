<?php

require_once __DIR__ . '/../models/Blog.php';
require_once __DIR__ . '/../utils/ApiError.php';
require_once __DIR__ . '/../utils/MultipartParser.php';

function listBlogs($req, $res) {
    $query = $req['query'] ?? [];
    
    $page = (int)($query['page'] ?? 1);
    $limit = min((int)($query['limit'] ?? 10), 50);
    $category = $query['category'] ?? null;
    $search = $query['search'] ?? null;
    
    // For admin access, allow viewing unpublished blogs
    $published = isset($query['published']) ? ($query['published'] === 'true' || $query['published'] === true) : null;
    
    $blogModel = new Blog();
    
    $filters = [];
    if ($category) $filters['category'] = $category;
    if ($search) $filters['search'] = $search;
    if ($published !== null) $filters['published'] = $published;
    
    $blogs = $blogModel->getAll($filters, $page, $limit);
    $total = $blogModel->getTotalCount($filters);
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $blogs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getBlog($req, $res) {
    $id = $req['params']['id'] ?? null;
    
    if (!$id) {
        throw new ApiError(400, 'Blog ID is required');
    }
    
    $blogModel = new Blog();
    $blog = $blogModel->getById($id);
    
    if (!$blog) {
        throw new ApiError(404, 'Blog not found');
    }
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $blog]);
}

function getBlogBySlug($req, $res) {
    $slug = $req['params']['slug'] ?? null;
    
    if (!$slug) {
        throw new ApiError(400, 'Blog slug is required');
    }
    
    $blogModel = new Blog();
    $blog = $blogModel->getBySlug($slug);
    
    if (!$blog) {
        throw new ApiError(404, 'Blog not found');
    }
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $blog]);
}

function createBlog($req, $res) {
    // Check if this is a multipart/form-data request (file upload)
    // Router now populates $_POST and $_FILES for PUT requests with multipart/form-data
    $isMultipart = !empty($_FILES) || !empty($_POST);
    
    if ($isMultipart) {
        // Handle FormData request
        // Router now populates $_POST for PUT requests with multipart/form-data
        $data = $req['bodyData'] ?? $_POST;
        
        // Convert string boolean to actual boolean for is_published
        if (isset($data['is_published'])) {
            $data['is_published'] = ($data['is_published'] === 'true' || $data['is_published'] === true || $data['is_published'] === '1' || $data['is_published'] === 1);
        }
        
        // Handle image upload - check for both single file and array structure
        // Also check for both 'featureImage' and 'image' field names
        $hasFileUpload = false;
        $file = null;
        $fileFieldName = null;
        
        // Debug: Log $_FILES structure
        error_log("createBlog - Request method: " . ($req['method'] ?? 'unknown'));
        error_log("createBlog - Content-Type: " . ($req['headers']['content-type'] ?? 'not set'));
        error_log("createBlog - \$_FILES structure: " . json_encode($_FILES));
        error_log("createBlog - \$_POST structure: " . json_encode($_POST));
        error_log("createBlog - bodyData: " . json_encode($req['bodyData'] ?? []));
        
        // Check for featureImage first, then image
        $fileFieldsToCheck = ['featureImage', 'image'];
        
        foreach ($fileFieldsToCheck as $fieldName) {
            if (!empty($_FILES[$fieldName])) {
                // Check if it's a single file or array structure
                if (isset($_FILES[$fieldName]['name'])) {
                    if (is_array($_FILES[$fieldName]['name'])) {
                        // Array structure - get first file
                        if (!empty($_FILES[$fieldName]['name'][0])) {
                            $file = [
                                'name' => $_FILES[$fieldName]['name'][0],
                                'type' => $_FILES[$fieldName]['type'][0],
                                'tmp_name' => $_FILES[$fieldName]['tmp_name'][0],
                                'error' => $_FILES[$fieldName]['error'][0],
                                'size' => $_FILES[$fieldName]['size'][0]
                            ];
                            $hasFileUpload = true;
                            $fileFieldName = $fieldName;
                            break;
                        }
                    } else {
                        // Single file structure
                        if (!empty($_FILES[$fieldName]['name'])) {
                            $file = $_FILES[$fieldName];
                            $hasFileUpload = true;
                            $fileFieldName = $fieldName;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($hasFileUpload && $file) {
            // Check file error - for manually parsed files, error might not be set or might be 0
            $fileError = $file['error'] ?? UPLOAD_ERR_OK;
            
            if ($fileError === UPLOAD_ERR_OK || $fileError === 0) {
                // New file uploaded
                $uploadDir = __DIR__ . '/../../public/uploads/blogs';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('blog_', true) . ($extension ? ".{$extension}" : '');
                $targetPath = $uploadDir . '/' . $filename;
                
                error_log("createBlog - Attempting to save file. Field: $fileFieldName, tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                error_log("createBlog - File details: name=" . $file['name'] . ", size=" . ($file['size'] ?? 'unknown') . ", error=" . $fileError);
                
                // Use saveUploadedFileOrTemp to handle both regular uploads and manually parsed files
                if (saveUploadedFileOrTemp($file['tmp_name'], $targetPath)) {
                    $data['image'] = '/uploads/blogs/' . $filename;
                    error_log("createBlog - Image uploaded successfully: " . $data['image']);
                } else {
                    error_log("createBlog - Failed to save uploaded file. tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                    error_log("createBlog - File exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
                    error_log("createBlog - Target dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
                }
            } else {
                error_log("createBlog - File upload error: " . $fileError . " (UPLOAD_ERR_OK=" . UPLOAD_ERR_OK . ")");
            }
        } else {
            // Check if an explicit image URL was provided in form data
            $imageFromForm = $data['image'] ?? null;
            if (!empty($imageFromForm) && is_string($imageFromForm) && trim($imageFromForm) !== '') {
                // Explicit image URL provided (not empty)
                $data['image'] = trim($imageFromForm);
                error_log("createBlog - Using image URL from form: " . $data['image']);
            } else {
                error_log("createBlog - No image file or URL provided");
            }
        }
    } else {
        // Handle JSON request
        $data = json_decode($req['body'], true);
    }
    
    if (empty($data['title'])) {
        throw new ApiError(400, 'Title is required');
    }
    
    if (empty($data['content'])) {
        throw new ApiError(400, 'Content is required');
    }
    
    // Debug: Log what's being created
    error_log("createBlog - Data being created: " . json_encode($data));
    error_log("createBlog - Image field in data: " . (isset($data['image']) ? $data['image'] : 'NOT SET'));
    
    $blogModel = new Blog();
    $id = $blogModel->createBlog($data);
    $newBlog = $blogModel->getById($id);
    
    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode(['data' => $newBlog]);
}

function updateBlog($req, $res) {
    $id = $req['params']['id'] ?? null;
    
    if (!$id) {
        throw new ApiError(400, 'Blog ID is required');
    }
    
    $blogModel = new Blog();
    $existingBlog = $blogModel->getById($id);
    
    if (!$existingBlog) {
        throw new ApiError(404, 'Blog not found');
    }
    
    // Check if this is a multipart/form-data request (file upload)
    // Router now populates $_POST and $_FILES for PUT requests with multipart/form-data
    $isMultipart = !empty($_FILES) || !empty($_POST);
    
    if ($isMultipart) {
        // Handle FormData request
        // Router now populates $_POST for PUT requests with multipart/form-data
        $data = $req['bodyData'] ?? $_POST;
        
        // Get image value from form data before we modify $data
        $imageFromForm = $data['image'] ?? null;
        
        // Remove image field from data initially to prevent overwriting with empty/null values
        // We'll add it back only if we have a new value
        unset($data['image']);
        
        // Convert string boolean to actual boolean for is_published
        if (isset($data['is_published'])) {
            $data['is_published'] = ($data['is_published'] === 'true' || $data['is_published'] === true || $data['is_published'] === '1' || $data['is_published'] === 1);
        }
        
        // Handle image upload - priority: new file upload > explicit image URL > keep existing
        // Check for file upload - handle both single file and array structure
        // Also check for both 'featureImage' and 'image' field names
        $hasFileUpload = false;
        $file = null;
        $fileFieldName = null;
        
        // Debug: Log $_FILES structure and request details
        error_log("updateBlog - Request method: " . ($req['method'] ?? 'unknown'));
        error_log("updateBlog - Content-Type: " . ($req['headers']['content-type'] ?? 'not set'));
        error_log("updateBlog - \$_FILES structure: " . json_encode($_FILES));
        error_log("updateBlog - \$_POST structure: " . json_encode($_POST));
        error_log("updateBlog - bodyData: " . json_encode($req['bodyData'] ?? []));
        
        // Check for featureImage first, then image
        $fileFieldsToCheck = ['featureImage', 'image'];
        
        foreach ($fileFieldsToCheck as $fieldName) {
            if (!empty($_FILES[$fieldName])) {
                // Check if it's a single file or array structure
                if (isset($_FILES[$fieldName]['name'])) {
                    if (is_array($_FILES[$fieldName]['name'])) {
                        // Array structure - get first file
                        if (!empty($_FILES[$fieldName]['name'][0])) {
                            $file = [
                                'name' => $_FILES[$fieldName]['name'][0],
                                'type' => $_FILES[$fieldName]['type'][0],
                                'tmp_name' => $_FILES[$fieldName]['tmp_name'][0],
                                'error' => $_FILES[$fieldName]['error'][0],
                                'size' => $_FILES[$fieldName]['size'][0]
                            ];
                            $hasFileUpload = true;
                            $fileFieldName = $fieldName;
                            break;
                        }
                    } else {
                        // Single file structure
                        if (!empty($_FILES[$fieldName]['name'])) {
                            $file = $_FILES[$fieldName];
                            $hasFileUpload = true;
                            $fileFieldName = $fieldName;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($hasFileUpload && $file) {
            // Check file error - for manually parsed files, error might not be set or might be 0
            $fileError = $file['error'] ?? UPLOAD_ERR_OK;
            
            if ($fileError === UPLOAD_ERR_OK || $fileError === 0) {
                // New file uploaded
                $uploadDir = __DIR__ . '/../../public/uploads/blogs';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('blog_', true) . ($extension ? ".{$extension}" : '');
                $targetPath = $uploadDir . '/' . $filename;
                
                error_log("updateBlog - Attempting to save file. Field: $fileFieldName, tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                error_log("updateBlog - File details: name=" . $file['name'] . ", size=" . ($file['size'] ?? 'unknown') . ", error=" . $fileError);
                
                // Use saveUploadedFileOrTemp to handle both regular uploads and manually parsed files from PUT requests
                if (saveUploadedFileOrTemp($file['tmp_name'], $targetPath)) {
                    $data['image'] = '/uploads/blogs/' . $filename;
                    error_log("updateBlog - Image uploaded successfully: " . $data['image']);
                } else {
                    error_log("updateBlog - Failed to save uploaded file. tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                    error_log("updateBlog - File exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
                    error_log("updateBlog - Target dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
                }
            } else {
                error_log("updateBlog - File upload error: " . $fileError . " (UPLOAD_ERR_OK=" . UPLOAD_ERR_OK . ")");
            }
        } elseif (!empty($imageFromForm) && is_string($imageFromForm) && trim($imageFromForm) !== '') {
            // Explicit image URL provided in form data (not empty)
            $data['image'] = trim($imageFromForm);
            error_log("updateBlog - Using image URL from form: " . $data['image']);
        } else {
            // No new file and no valid image URL provided
            // Don't include image in $data - this preserves the existing image in database
            // The update will not modify the image field
            error_log("updateBlog - No image update, preserving existing image");
        }
    } else {
        // Handle JSON request
        $data = json_decode($req['body'], true);
        
        // If image is not provided in JSON, don't update it (keeps existing)
        if (!isset($data['image'])) {
            unset($data['image']);
        } elseif ($data['image'] === '' || $data['image'] === null) {
            // Explicitly set to empty/null - allow it to remove image
            $data['image'] = null;
        }
    }
    
    // Validate required fields
    if (empty($data['title'])) {
        throw new ApiError(400, 'Title is required');
    }
    
    if (empty($data['content'])) {
        throw new ApiError(400, 'Content is required');
    }
    
    // Debug: Log what's being updated
    error_log("updateBlog - Data being updated: " . json_encode($data));
    error_log("updateBlog - Image field in data: " . (isset($data['image']) ? $data['image'] : 'NOT SET'));
    
    $blogModel->updateBlog($id, $data);
    $updatedBlog = $blogModel->getById($id);
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $updatedBlog]);
}

function deleteBlog($req, $res) {
    $id = $req['params']['id'] ?? null;
    
    if (!$id) {
        throw new ApiError(400, 'Blog ID is required');
    }
    
    $blogModel = new Blog();
    $existingBlog = $blogModel->getById($id);
    
    if (!$existingBlog) {
        throw new ApiError(404, 'Blog not found');
    }
    
    $blogModel->deleteBlog($id);
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Blog deleted successfully']);
}

