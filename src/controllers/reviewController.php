<?php

require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../utils/ApiError.php';

function createReview($req, $res) {
    $data = json_decode($req['body'], true);
    $userId = $req['user']['sub'] ?? null;

    if (!$userId) {
        throw new ApiError(401, 'Authentication required');
    }

    $packageId = $data['packageId'] ?? null;
    $rating = $data['rating'] ?? null;
    $comment = $data['comment'] ?? null;

    if (!$packageId || !$rating) {
        throw new ApiError(400, 'Package ID and rating are required');
    }

    if ($rating < 1 || $rating > 5) {
        throw new ApiError(400, 'Rating must be between 1 and 5');
    }

    $packageModel = new Package();
    $package = $packageModel->findById($packageId);
    
    if (!$package) {
        throw new ApiError(404, 'Package not found');
    }

    $reviewModel = new Review();
    $existingReview = $reviewModel->findByUserAndPackage($userId, $packageId);
    
    if ($existingReview) {
        throw new ApiError(409, 'Review already exists for this package');
    }

    $reviewData = [
        'user_id' => $userId,
        'package_id' => $packageId,
        'rating' => $rating,
        'comment' => $comment,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $reviewId = $reviewModel->create($reviewData);
    $review = $reviewModel->findById($reviewId);

    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode(['data' => $review]);
}

function getReviews($req, $res) {
    $packageId = $req['query']['packageId'] ?? null;
    $page = (int)($req['query']['page'] ?? 1);
    $limit = (int)($req['query']['limit'] ?? 10);

    if (!$packageId) {
        throw new ApiError(400, 'Package ID is required');
    }

    $reviewModel = new Review();
    $reviews = $reviewModel->findByPackage($packageId, $page, $limit);
    $ratingStats = $reviewModel->getAverageRating($packageId);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $reviews,
        'rating' => $ratingStats,
    ]);
}

function updateReview($req, $res) {
    $id = $req['params']['id'] ?? null;
    
    if (!$id) {
        throw new ApiError(400, 'Review ID is required');
    }
    
    $userId = $req['user']['sub'] ?? null;
    
    if (!$userId) {
        throw new ApiError(401, 'Authentication required');
    }
    
    $reviewModel = new Review();
    $existingReview = $reviewModel->findById($id);
    
    if (!$existingReview) {
        throw new ApiError(404, 'Review not found');
    }
    
    // Check if user owns this review (users can only edit their own reviews, admins can edit any)
    $userRole = $req['user']['role'] ?? 'user';
    if ($existingReview['user_id'] != $userId && $userRole !== 'admin') {
        throw new ApiError(403, 'You can only edit your own reviews');
    }
    
    // Check if this is a multipart/form-data request
    // Router now populates $_POST for PUT requests with multipart/form-data
    $isMultipart = !empty($_POST);
    
    if ($isMultipart) {
        // Handle FormData request
        $data = $req['bodyData'] ?? $_POST;
    } else {
        // Handle JSON request
        $data = json_decode($req['body'], true);
    }
    
    // Extract and validate data
    $rating = isset($data['rating']) ? (int)$data['rating'] : null;
    $comment = $data['comment'] ?? null;
    
    // Initialize update data array
    $updateData = [];
    
    // Validate rating if provided
    if ($rating !== null) {
        if ($rating < 1 || $rating > 5) {
            throw new ApiError(400, 'Rating must be between 1 and 5');
        }
        $updateData['rating'] = $rating;
    }
    
    // Update comment if provided
    if ($comment !== null) {
        $updateData['comment'] = $comment;
    }
    
    // If no data to update, return error
    if (empty($updateData)) {
        throw new ApiError(400, 'No valid fields to update');
    }
    
    // Set updated_at timestamp
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    // Update the review
    $reviewModel->update($id, $updateData);
    $updatedReview = $reviewModel->findById($id);
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['data' => $updatedReview]);
}

function deleteReview($req, $res) {
    $id = $req['params']['id'] ?? null;
    
    if (!$id) {
        throw new ApiError(400, 'Review ID is required');
    }
    
    $userId = $req['user']['sub'] ?? null;
    
    if (!$userId) {
        throw new ApiError(401, 'Authentication required');
    }
    
    $reviewModel = new Review();
    $existingReview = $reviewModel->findById($id);
    
    if (!$existingReview) {
        throw new ApiError(404, 'Review not found');
    }
    
    // Check if user owns this review (users can only delete their own reviews, admins can delete any)
    $userRole = $req['user']['role'] ?? 'user';
    if ($existingReview['user_id'] != $userId && $userRole !== 'admin') {
        throw new ApiError(403, 'You can only delete your own reviews');
    }
    
    $reviewModel->delete($id);
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Review deleted successfully']);
}

