import 'package:flutter/material.dart';
import '../api_connection/api_connection.dart';
import '../user_model/user_model.dart';

class PostDetail extends StatefulWidget {
  final User user;
  final Function() onClose;
  final dynamic post;

  const PostDetail({
    super.key,
    required this.user,
    required this.onClose,
    required this.post,
  });

  @override
  State<PostDetail> createState() => _PostDetailState();
}

class _PostDetailState extends State<PostDetail> {
  @override
  Widget build(BuildContext context) {
    final post = widget.post;

    return SizedBox(
      width: double.infinity,
      height: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header with close button only
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Post Image',
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
          ),

          const SizedBox(height: 10),

          // Image container
          if (post['post_image'] != null && post['post_image'].toString().isNotEmpty)
            Flexible(
              fit: FlexFit.tight,
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  image: DecorationImage(
                    image: NetworkImage('${API.hostConnect}/${post['post_image']}'),
                    fit: BoxFit.contain,
                  ),
                ),
              ),
            )
          else
          // Placeholder when no image
            Flexible(
              fit: FlexFit.tight,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.image_not_supported,
                      size: 80,
                      color: Colors.grey[400],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      'No Image Available',
                      style: TextStyle(
                        fontSize: 16,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}