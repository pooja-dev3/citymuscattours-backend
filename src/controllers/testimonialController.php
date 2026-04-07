<?php

require_once __DIR__ . '/../models/Testimonial.php';
require_once __DIR__ . '/../utils/ApiError.php';
require_once __DIR__ . '/../utils/MultipartParser.php';

function listTestimonials($req, $res) {
    $page = (int)($req['query']['page'] ?? 1);
    $limit = (int)($req['query']['limit'] ?? 100);
    $filters = [];

    // Only filter by active status if explicitly requested (for public API)
    if (isset($req['query']['active'])) {
        $filters['is_active'] = filter_var($req['query']['active'], FILTER_VALIDATE_BOOLEAN);
    }

    if (isset($req['query']['search'])) {
        $filters['search'] = $req['query']['search'];
    }

    $testimonialModel = new Testimonial();
    $testimonials = $testimonialModel->getAll($filters, $page, $limit);
    $total = $testimonialModel->getTotalCount($filters);
    $totalPages = ceil($total / $limit);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $testimonials,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $totalPages,
        ],
    ]);
}

function getTestimonial($req, $res) {
    $id = $req['params']['id'] ?? null;

    if (!$id) {
        throw new ApiError(400, 'Testimonial ID is required');
    }

    $testimonialModel = new Testimonial();
    $testimonial = $testimonialModel->getById($id);

    if (!$testimonial) {
        throw new ApiError(404, 'Testimonial not found');
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $testimonial]);
}

function createTestimonial($req, $res) {
    // Check if this is a multipart/form-data request (file upload)
    // Router now populates $_POST and $_FILES for PUT requests with multipart/form-data
    $isMultipart = !empty($_FILES) || !empty($_POST);
    
    if ($isMultipart) {
        // Handle FormData request
        // Router now populates $_POST for PUT requests with multipart/form-data
        $data = $req['bodyData'] ?? $_POST;
        
        // Handle avatar upload - check for both single file and array structure
        $hasFileUpload = false;
        $file = null;
        
        if (!empty($_FILES['avatar'])) {
            // Check if it's a single file or array structure
            if (isset($_FILES['avatar']['name'])) {
                if (is_array($_FILES['avatar']['name'])) {
                    // Array structure - get first file
                    if (!empty($_FILES['avatar']['name'][0])) {
                        $file = [
                            'name' => $_FILES['avatar']['name'][0],
                            'type' => $_FILES['avatar']['type'][0],
                            'tmp_name' => $_FILES['avatar']['tmp_name'][0],
                            'error' => $_FILES['avatar']['error'][0],
                            'size' => $_FILES['avatar']['size'][0]
                        ];
                        $hasFileUpload = true;
                    }
                } else {
                    // Single file structure
                    if (!empty($_FILES['avatar']['name'])) {
                        $file = $_FILES['avatar'];
                        $hasFileUpload = true;
                    }
                }
            }
        }
        
        if ($hasFileUpload && $file) {
            $fileError = $file['error'] ?? UPLOAD_ERR_OK;
            
            if ($fileError === UPLOAD_ERR_OK || $fileError === 0) {
                $uploadDir = __DIR__ . '/../../public/uploads/testimonials';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('avatar_', true) . ($extension ? ".{$extension}" : '');
                $targetPath = $uploadDir . '/' . $filename;
                
                // Use saveUploadedFileOrTemp to handle both regular uploads and manually parsed files from PUT requests
                if (saveUploadedFileOrTemp($file['tmp_name'], $targetPath)) {
                    $data['avatar'] = '/uploads/testimonials/' . $filename;
                    error_log("createTestimonial - Avatar uploaded successfully: " . $data['avatar']);
                } else {
                    error_log("createTestimonial - Failed to save uploaded file. tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                }
            }
        }
    } else {
        // Handle JSON request
        $data = json_decode($req['body'], true);
    }

    if (!$data) {
        throw new ApiError(400, 'Invalid data');
    }

    // Validate required fields
    if (empty($data['name'])) {
        throw new ApiError(400, 'Name is required');
    }

    if (empty($data['location'])) {
        throw new ApiError(400, 'Location is required');
    }

    if (empty($data['quote'])) {
        throw new ApiError(400, 'Quote is required');
    }

    // Validate rating if provided
    if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
        throw new ApiError(400, 'Rating must be between 1 and 5');
    }

    $testimonialModel = new Testimonial();
    $testimonialId = $testimonialModel->createTestimonial($data);
    $testimonial = $testimonialModel->getById($testimonialId);

    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode(['data' => $testimonial]);
}

function updateTestimonial($req, $res) {
    $id = $req['params']['id'] ?? null;

    if (!$id) {
        throw new ApiError(400, 'Testimonial ID is required');
    }

    $testimonialModel = new Testimonial();
    $testimonial = $testimonialModel->getById($id);

    if (!$testimonial) {
        throw new ApiError(404, 'Testimonial not found');
    }

    // Check if this is a multipart/form-data request (file upload)
    // Router now populates $_POST and $_FILES for PUT requests with multipart/form-data
    $isMultipart = !empty($_FILES) || !empty($_POST);
    
    if ($isMultipart) {
        // Handle FormData request
        // Router now populates $_POST for PUT requests with multipart/form-data
        $data = $req['bodyData'] ?? $_POST;
        
        // Get avatar value from form data before we modify $data
        $avatarFromForm = $data['avatar'] ?? null;
        
        // Remove avatar field from data initially to prevent overwriting with empty/null values
        // We'll add it back only if we have a new value
        unset($data['avatar']);
        
        // Handle avatar upload - priority: new file upload > explicit avatar URL > keep existing
        // Check for file upload - handle both single file and array structure
        $hasFileUpload = false;
        $file = null;
        
        // Debug: Log $_FILES structure
        error_log("updateTestimonial - Request method: " . ($req['method'] ?? 'unknown'));
        error_log("updateTestimonial - Content-Type: " . ($req['headers']['content-type'] ?? 'not set'));
        error_log("updateTestimonial - \$_FILES structure: " . json_encode($_FILES));
        error_log("updateTestimonial - \$_POST structure: " . json_encode($_POST));
        error_log("updateTestimonial - bodyData: " . json_encode($req['bodyData'] ?? []));
        
        if (!empty($_FILES['avatar'])) {
            // Check if it's a single file or array structure
            if (isset($_FILES['avatar']['name'])) {
                if (is_array($_FILES['avatar']['name'])) {
                    // Array structure - get first file
                    if (!empty($_FILES['avatar']['name'][0])) {
                        $file = [
                            'name' => $_FILES['avatar']['name'][0],
                            'type' => $_FILES['avatar']['type'][0],
                            'tmp_name' => $_FILES['avatar']['tmp_name'][0],
                            'error' => $_FILES['avatar']['error'][0],
                            'size' => $_FILES['avatar']['size'][0]
                        ];
                        $hasFileUpload = true;
                    }
                } else {
                    // Single file structure
                    if (!empty($_FILES['avatar']['name'])) {
                        $file = $_FILES['avatar'];
                        $hasFileUpload = true;
                    }
                }
            }
        }
        
        if ($hasFileUpload && $file) {
            // Check file error - for manually parsed files, error might not be set or might be 0
            $fileError = $file['error'] ?? UPLOAD_ERR_OK;
            
            if ($fileError === UPLOAD_ERR_OK || $fileError === 0) {
                // New file uploaded
                $uploadDir = __DIR__ . '/../../public/uploads/testimonials';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('avatar_', true) . ($extension ? ".{$extension}" : '');
                $targetPath = $uploadDir . '/' . $filename;
                
                error_log("updateTestimonial - Attempting to save file. tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                error_log("updateTestimonial - File details: name=" . $file['name'] . ", size=" . ($file['size'] ?? 'unknown') . ", error=" . $fileError);
                
                // Use saveUploadedFileOrTemp to handle both regular uploads and manually parsed files from PUT requests
                if (saveUploadedFileOrTemp($file['tmp_name'], $targetPath)) {
                    $data['avatar'] = '/uploads/testimonials/' . $filename;
                    error_log("updateTestimonial - Avatar uploaded successfully: " . $data['avatar']);
                } else {
                    error_log("updateTestimonial - Failed to save uploaded file. tmp_name: " . $file['tmp_name'] . ", target: " . $targetPath);
                    error_log("updateTestimonial - File exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
                    error_log("updateTestimonial - Target dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
                }
            } else {
                error_log("updateTestimonial - File upload error: " . $fileError . " (UPLOAD_ERR_OK=" . UPLOAD_ERR_OK . ")");
            }
        } elseif (!empty($avatarFromForm) && is_string($avatarFromForm) && trim($avatarFromForm) !== '') {
            // Explicit avatar URL provided in form data (not empty)
            $data['avatar'] = trim($avatarFromForm);
            error_log("updateTestimonial - Using avatar URL from form: " . $data['avatar']);
        } else {
            // No new file and no valid avatar URL provided
            // Don't include avatar in $data - this preserves the existing avatar in database
            // The update will not modify the avatar field
            error_log("updateTestimonial - No avatar update, preserving existing avatar");
        }
    } else {
        // Handle JSON request
        $data = json_decode($req['body'], true);
        
        // If avatar is not provided in JSON, don't update it (keeps existing)
        if (!isset($data['avatar'])) {
            unset($data['avatar']);
        } elseif ($data['avatar'] === '' || $data['avatar'] === null) {
            // Explicitly set to empty/null - allow it to remove avatar
            $data['avatar'] = null;
        }
    }

    if (!$data) {
        throw new ApiError(400, 'Invalid data');
    }

    // Validate rating if provided
    if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
        throw new ApiError(400, 'Rating must be between 1 and 5');
    }

    // Debug: Log what's being updated
    error_log("updateTestimonial - Data being updated: " . json_encode($data));
    error_log("updateTestimonial - Avatar field in data: " . (isset($data['avatar']) ? $data['avatar'] : 'NOT SET'));

    $testimonialModel->updateTestimonial($id, $data);
    $updatedTestimonial = $testimonialModel->getById($id);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $updatedTestimonial]);
}

function deleteTestimonial($req, $res) {
    $id = $req['params']['id'] ?? null;

    if (!$id) {
        throw new ApiError(400, 'Testimonial ID is required');
    }

    $testimonialModel = new Testimonial();
    $testimonial = $testimonialModel->getById($id);

    if (!$testimonial) {
        throw new ApiError(404, 'Testimonial not found');
    }

    $testimonialModel->deleteTestimonial($id);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Testimonial deleted successfully']);
}

