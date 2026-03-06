import 'package:flutter/material.dart';
import '../user_model/user_model.dart';
import '../service/post_service.dart';
import '../api_connection/api_connection.dart';
import 'post_details.dart';

class MyPosts extends StatefulWidget {
  final User user;
  final Function() onClose;
  final Function()? onPostChanged; // Add callback for when posts change

  const MyPosts({
    super.key,
    required this.user,
    required this.onClose,
    this.onPostChanged, // Optional callback
  });

  @override
  State<MyPosts> createState() => _MyPostsState();
}

class _MyPostsState extends State<MyPosts> {
  List<dynamic> _userPosts = [];
  bool _isLoading = true;
  bool _hasError = false;
  String _errorMessage = '';

  @override
  void initState() {
    super.initState();
    _loadUserPosts();
  }

  Future<void> _loadUserPosts() async {
    setState(() {
      _isLoading = true;
      _hasError = false;
    });

    try {
      var result = await PostService.getUserPosts(widget.user.id);

      if (result['success'] == true) {
        setState(() {
          _userPosts = result['posts'] ?? [];
          _isLoading = false;
        });
      } else {
        setState(() {
          _hasError = true;
          _errorMessage = result['message'] ?? 'Failed to load posts';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _hasError = true;
        _errorMessage = 'Error: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _deletePost(int postId) async {
    setState(() {
      _isLoading = true;
    });

    try {
      var result = await PostService.deletePost(postId, userId: widget.user.id);

      if (result['success'] == true) {
        // Remove the post from the local list
        setState(() {
          _userPosts.removeWhere((post) => post['id'] == postId);
          _isLoading = false;
        });

        // Notify parent that posts have changed (to refresh home page)
        if (widget.onPostChanged != null) {
          widget.onPostChanged!();
        }

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Post deleted successfully'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _isLoading = false;
        });

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Failed to delete post'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _toggleDonatedStatus(int postId, bool currentStatus) async {
    setState(() {
      _isLoading = true;
    });

    try {
      // Calculate the new status (opposite of current)
      bool newStatus = !currentStatus;

      var result = await PostService.updatePostStatus(
          postId,
          newStatus,  // Send the new status
          userId: widget.user.id
      );

      if (result['success'] == true) {
        // Update the post in the local list with the new status
        setState(() {
          int index = _userPosts.indexWhere((post) => post['id'] == postId);
          if (index != -1) {
            _userPosts[index]['is_donated'] = newStatus ? 1 : 0;
          }
          _isLoading = false;
        });

        // Notify parent that posts have changed (to refresh home page)
        if (widget.onPostChanged != null) {
          widget.onPostChanged!();
        }

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(newStatus ? 'Marked as Donated' : 'Marked as Available'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _isLoading = false;
        });

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Failed to update status'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  String _formatDate(String? dateString) {
    if (dateString == null) return '';
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  Widget _buildInfoChip(String text, IconData icon) {
    return Chip(
      label: Text(
        text,
        style: const TextStyle(fontSize: 10),
      ),
      avatar: Icon(icon, size: 12),
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
      visualDensity: VisualDensity.compact,
      padding: const EdgeInsets.symmetric(horizontal: 4),
      labelPadding: const EdgeInsets.symmetric(horizontal: 4),
    );
  }

  void _showDeleteConfirmation(int postId) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Delete Post'),
          content: const Text('Are you sure you want to delete this post? This action cannot be undone.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                _deletePost(postId);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
              ),
              child: const Text('Delete'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'My Posts',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: widget.onClose,
              ),
            ],
          ),
          const Divider(),
          const SizedBox(height: 10),

          // Refresh button
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton.icon(
                onPressed: _isLoading ? null : _loadUserPosts,
                icon: Icon(
                  Icons.refresh,
                  size: 16,
                  color: Colors.blue[700],
                ),
                label: Text(
                  'Refresh',
                  style: TextStyle(color: Colors.blue[700]),
                ),
              ),
            ],
          ),

          const SizedBox(height: 10),

          // Content
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _hasError
                ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.error_outline,
                    size: 60,
                    color: Colors.red[300],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    'Error loading posts',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    _errorMessage,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[500],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: _loadUserPosts,
                    child: const Text('Try Again'),
                  ),
                ],
              ),
            )
                : _userPosts.isEmpty
                ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.post_add,
                    size: 80,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    'No posts yet',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    'Create your first post!',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[500],
                    ),
                  ),
                ],
              ),
            )
                : ListView.builder(
              itemCount: _userPosts.length,
              itemBuilder: (context, index) {
                final post = _userPosts[index];
                bool isDonated = post['is_donated'] == 1 || post['is_donated'] == true;

                return _buildMyPostCard(post, isDonated);
              },
            ),
          ),
        ],
      ),
    );
  }

  // Separate method for my post card with transparent styling
  Widget _buildMyPostCard(Map<String, dynamic> post, bool isDonated) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey.shade200, width: 1),
      ),
      color: isDonated
          ? Colors.grey.shade50.withOpacity(0.8) // Transparent for donated
          : Colors.white.withOpacity(0.8), // Slightly transparent white
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Post type and anonymous badge
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: post['type'] == 'SELL'
                        ? Colors.green.withOpacity(0.1)
                        : (post['type'] == 'GIVE'
                        ? Colors.blue.withOpacity(0.1)
                        : Colors.orange.withOpacity(0.1)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    post['type'] == 'SELL'
                        ? 'For Sale'
                        : (post['type'] == 'GIVE' ? 'Give Away' : 'Requesting'),
                    style: TextStyle(
                      fontSize: 12,
                      color: post['type'] == 'SELL'
                          ? Colors.green[700]
                          : (post['type'] == 'GIVE' ? Colors.blue[700] : Colors.orange[700]),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                if (post['is_anonymous'] == 1)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.grey.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Text(
                      'Anonymous',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey,
                      ),
                    ),
                  ),
                const Spacer(),
                // Only show price for SELL type
                if (post['type'] == 'SELL' && post['price'] != null && post['price'] > 0)
                  Text(
                    '৳${post['price']}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.green,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 10),

            // Title and author
            Text(
              post['title'] ?? 'No Title',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            if (post['author'] != null && post['author'].toString().isNotEmpty)
              Text(
                'by ${post['author']}',
                style: const TextStyle(
                  fontSize: 14,
                  color: Colors.grey,
                ),
              ),
            const SizedBox(height: 8),

            // Description
            if (post['description']?.toString().isNotEmpty == true)
              Text(
                post['description'].toString(),
                style: const TextStyle(fontSize: 14),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: 8),

            // Chips
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                if (post['batch_year'] != null && post['batch_year'].toString().isNotEmpty)
                  _buildInfoChip('Batch: ${post['batch_year']}', Icons.school),
                if (post['department'] != null && post['department'].toString().isNotEmpty)
                  _buildInfoChip(post['department'].toString(), Icons.business),
                if (post['location'] != null && post['location'].toString().isNotEmpty)
                  _buildInfoChip(post['location'].toString(), Icons.location_on),
              ],
            ),

            // Contact and date
            const SizedBox(height: 8),
            Row(
              children: [
                if (post['contact_number'] != null && post['contact_number'].toString().isNotEmpty)
                  Expanded(
                    child: Row(
                      children: [
                        const Icon(Icons.phone, size: 14, color: Colors.grey),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            post['contact_number'].toString(),
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                        ),
                      ],
                    ),
                  ),
                Text(
                  _formatDate(post['created_at']),
                  style: const TextStyle(fontSize: 11, color: Colors.grey),
                ),
              ],
            ),

            const SizedBox(height: 8),

            // Post image preview if available
            if (post['post_image'] != null && post['post_image'].toString().isNotEmpty)
              Container(
                height: 100,
                width: double.infinity,
                margin: const EdgeInsets.only(top: 8),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  image: DecorationImage(
                    image: NetworkImage('${API.hostConnect}/${post['post_image']}'),
                    fit: BoxFit.cover,
                  ),
                ),
              ),

            const Divider(),

            // Status Toggle and Action buttons
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Donated/Available Toggle
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                  decoration: BoxDecoration(
                    color: isDonated ? Colors.green.withOpacity(0.1) : Colors.orange.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: isDonated ? Colors.green : Colors.orange,
                      width: 1,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Switch(
                        value: isDonated,
                        onChanged: _isLoading ? null : (value) {
                          _toggleDonatedStatus(post['id'], isDonated);
                        },
                        activeColor: Colors.redAccent,
                        inactiveTrackColor: Colors.green.withOpacity(0.3),
                        inactiveThumbColor: Colors.greenAccent,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: Text(
                          isDonated ? 'Donated' : 'Available',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: isDonated ? Colors.redAccent[700] : Colors.greenAccent[700],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // Action buttons view and delete
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextButton.icon(
                      onPressed: () {
                        showDialog(
                          context: context,
                          builder: (context) => Dialog(
                            child: Container(
                              padding: const EdgeInsets.all(20),
                              width: 500,
                              height: 600,
                              child: PostDetail(
                                user: widget.user,
                                onClose: () => Navigator.pop(context),
                                post: post,
                              ),
                            ),
                          ),
                        );
                      },
                      icon: const Icon(Icons.visibility, size: 16),
                      label: const Text('View'),
                    ),
                    const SizedBox(width: 4),
                    TextButton.icon(
                      onPressed: () {
                        _showDeleteConfirmation(post['id']);
                      },
                      icon: const Icon(Icons.delete, size: 16, color: Colors.red),
                      label: Text(
                        'Delete',
                        style: TextStyle(color: Colors.red[700]),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}