import 'package:book_swaps/home_gui/password_change.dart';
import 'package:book_swaps/home_gui/post_details.dart';
import 'package:book_swaps/home_gui/update_profile.dart';
import 'package:book_swaps/user_page/login_page.dart';
import 'package:flutter/material.dart';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'my_posts.dart';

import '../service/post_service.dart';
import '../service/profile_service.dart';
import '../user_model/user_model.dart';
import 'create_post.dart';
import 'email_settings.dart';
import '../api_connection/api_connection.dart';

class HomePage extends StatefulWidget {
  final User user;
  const HomePage({super.key, required this.user});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  dynamic _selectedItem;

  List<dynamic> _posts = [];

  List<dynamic> _filteredPosts = [];

  // Search controller
  final TextEditingController _searchController = TextEditingController();

  bool _isSearching = false;

  // Sample profile options
  final List<Map<String, dynamic>> _profileOptions = [
    {'icon': Icons.person, 'title': 'Update Profile'},
    {'icon': Icons.lock, 'title': 'Change Password'},
    {'icon': Icons.email, 'title': 'Email Settings'},
    {'icon': Icons.add_box, 'title': 'Create Post'},
    {'icon': Icons.post_add, 'title': 'My Posts'},  // ← New option
    {'icon': Icons.exit_to_app, 'title': 'Logout'},
  ];

  // For profile image
  File? _profileImage;
  final ImagePicker _picker = ImagePicker();

  // For create post form
  final _postFormKey = GlobalKey<FormState>();

  bool _forSale = true;
  bool _anonymous = false;
  File? _bookImage;

  // Current user data that can be updated
  late User _currentUser;

  @override
  void initState() {
    super.initState();
    // Initialize with the widget user
    _currentUser = widget.user;
    _loadUserProfile();
    _loadPosts();

    // Add listener for search changes
    _searchController.addListener(_filterPosts);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  // Filter posts based on search query
  void _filterPosts() {
    String query = _searchController.text.toLowerCase().trim();

    if (query.isEmpty) {
      setState(() {
        _filteredPosts = List.from(_posts);
        _isSearching = false;
      });
    } else {
      setState(() {
        _filteredPosts = _posts.where((post) {
          // Search by book title
          bool titleMatch = post['title']?.toString().toLowerCase().contains(query) ?? false;

          // Search by author name
          bool authorMatch = post['author']?.toString().toLowerCase().contains(query) ?? false;

          // Search by user name (posted person's name)
          bool userNameMatch = post['user_name']?.toString().toLowerCase().contains(query) ?? false;

          return titleMatch || authorMatch || userNameMatch;
        }).toList();
        _isSearching = true;
      });
    }
  }

  // Clear search
  void _clearSearch() {
    _searchController.clear();
    setState(() {
      _filteredPosts = List.from(_posts);
      _isSearching = false;
    });
  }

  // Update _selectItem to accept dynamic type
  void _selectItem(dynamic item) {
    setState(() {
      _selectedItem = item;
    });
  }

  void _clearSelection() {
    setState(() {
      _selectedItem = null;
    });
  }

  // Add this method to load posts
  void _loadPosts() async {
    var result = await PostService.getPosts();
    if (result['success'] == true) {
      setState(() {
        _posts = result['posts'] ?? [];
        _filteredPosts = List.from(_posts);
      });
    }
  }

  // Update the _loadUserProfile method to refresh the current user data
  void _loadUserProfile() async {
    var result = await ProfileService.getProfile(_currentUser.id);

    if (result['success'] == true) {
      setState(() {
        // Update the current user with fresh data from the database
        _currentUser = User(
          id: _currentUser.id,
          name: result['name'] ?? _currentUser.name,
          email: result['email'] ?? _currentUser.email,
          studentId: result['student_id']?.isNotEmpty == true ? result['student_id'] : null,
          department: result['department']?.isNotEmpty == true ? result['department'] : null,
          currentLocation: result['current_location']?.isNotEmpty == true ? result['current_location'] : null,
          permanentLocation: result['permanent_location']?.isNotEmpty == true ? result['permanent_location'] : null,
          bio: result['bio']?.isNotEmpty == true ? result['bio'] : null,
          profileImage: result['profile_image']?.isNotEmpty == true ? result['profile_image'] : null,
        );
      });
    }
  }

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  // Pick image from gallery - remove profile image handling
  Future<void> _pickImage(bool isProfile) async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      if (isProfile) {
        _showSnackBar('Please use "Update Profile" to change profile image');
      } else {
        setState(() {
          _bookImage = File(image.path);
        });
      }
    }
  }

  // Handle logout
  void _handleLogout() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Logout'),
          content: const Text('Are you sure you want to logout?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (context) => const MyLogin()),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
              ),
              child: const Text('Logout'),
            ),
          ],
        );
      },
    );
  }

  // Handle post submission
  void _submitPost() {
    if (_postFormKey.currentState!.validate()) {
      _postFormKey.currentState!.save();
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Post created successfully!'))
      );
      _clearSelection();
    }
  }

  // Add this helper method
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

  // Add this date formatting method
  String _formatDate(String? dateString) {
    if (dateString == null) return '';
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year} ${date.hour}:${date.minute.toString().padLeft(2, '0')}';
    } catch (e) {
      return dateString;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('image_collection/pictureBody4.jpg'),
            fit: BoxFit.cover,
          ),
        ),
        child: Row(
          children: [
            // Left Column - Profile Options
            Container(
              width: 300,
              decoration: BoxDecoration(
                color: Colors.greenAccent.withOpacity(0.4),
                borderRadius: const BorderRadius.only(
                  topRight: Radius.circular(15),
                  bottomRight: Radius.circular(15),
                ),
              ),
              child: Column(
                children: [
                  Expanded(
                    child: SingleChildScrollView(
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 20),
                            // Updated profile picture section
                            GestureDetector(
                              onTap: () => _selectItem('Update Profile'),
                              child: Center(
                                child: CircleAvatar(
                                  radius: 60,
                                  backgroundColor: Colors.grey[300],
                                  backgroundImage: _currentUser.profileImage != null &&
                                      _currentUser.profileImage!.isNotEmpty
                                      ? NetworkImage('${API.hostConnect}/${_currentUser.profileImage!}')
                                      : null,
                                  child: _currentUser.profileImage != null &&
                                      _currentUser.profileImage!.isNotEmpty
                                      ? null
                                      : const Icon(Icons.person, size: 60, color: Colors.grey),
                                ),
                              ),
                            ),
                            const SizedBox(height: 10),
                            Center(
                              child: Text(
                                _currentUser.name, // Use updated name
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                            const SizedBox(height: 30),
                            ..._profileOptions.map((option) => ListTile(
                              leading: Icon(option['icon'], color: Colors.blue),
                              title: Text(option['title']),
                              onTap: () {
                                if (option['title'] == 'Logout') {
                                  _handleLogout();
                                } else {
                                  _selectItem(option['title']);
                                }
                              },
                            )).toList(),
                            const SizedBox(height: 20),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Middle Column - Posts (UPDATED with search bar)
            Expanded(
              flex: _selectedItem == null ? 2 : 1,
              child: Container(
                decoration: BoxDecoration(color: Colors.black.withOpacity(0.1)),
                child: Column(
                  children: [
                    // Search Bar Container
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withOpacity(0),
                            spreadRadius: 1,
                            blurRadius: 5,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Container(
                              decoration: BoxDecoration(
                                color: Colors.grey[200],
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: TextField(
                                controller: _searchController,
                                decoration: InputDecoration(
                                  hintText: 'Search by book title, author, or person...',
                                  prefixIcon: const Icon(Icons.search, color: Colors.grey),
                                  suffixIcon: _isSearching
                                      ? IconButton(
                                    icon: const Icon(Icons.clear, color: Colors.grey),
                                    onPressed: _clearSearch,
                                  )
                                      : null,
                                  border: InputBorder.none,
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          // Search filter chip to show count
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.3),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              '${_filteredPosts.length} found',
                              style: const TextStyle(
                                color: Colors.yellowAccent,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Posts List
                    Expanded(
                      child: SingleChildScrollView(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            children: [
                              // Show message if no posts found
                              if (_filteredPosts.isEmpty)
                                Container(
                                  padding: const EdgeInsets.all(40),
                                  child: Column(
                                    children: [
                                      Icon(
                                        Icons.search_off,
                                        size: 80,
                                        color: Colors.red[400],
                                      ),
                                      const SizedBox(height: 20),
                                      Text(
                                        _isSearching
                                            ? 'No posts match your search'
                                            : 'No posts available',
                                        style: TextStyle(
                                          fontSize: 18,
                                          color: Colors.yellowAccent[600],
                                        ),
                                      ),
                                      if (_isSearching)
                                        TextButton(
                                          onPressed: _clearSearch,
                                          child: const Text('Clear Search'),
                                        ),
                                    ],
                                  ),
                                )
                              else
                              // Posts List
                                ..._filteredPosts.map((post) => Card(
                                  margin: const EdgeInsets.only(bottom: 15),
                                  elevation: 3,
                                  color: Colors.white.withOpacity(0.8),
                                  child: Padding(
                                    padding: const EdgeInsets.all(12),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        // Post header with user info and post type
                                        Row(
                                          children: [
                                            CircleAvatar(
                                              backgroundImage: (post['user_profile_image'] != null &&
                                                  post['user_profile_image'].toString().isNotEmpty)
                                                  ? NetworkImage('${API.hostConnect}/${post['user_profile_image']}') as ImageProvider
                                                  : const AssetImage('image_collection/profile.jpg') as ImageProvider,
                                              child: (post['user_profile_image'] == null ||
                                                  post['user_profile_image'].toString().isEmpty)
                                                  ? const Icon(Icons.person, color: Colors.white, size: 20)
                                                  : null,
                                              radius: 20,
                                            ),
                                            const SizedBox(width: 10),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    post['user_name']?.toString() ?? 'Unknown User',
                                                    style: const TextStyle(
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 14,
                                                    ),
                                                  ),
                                                  Text(
                                                    post['type'] == 'SELL'
                                                        ? 'Selling'
                                                        : (post['type'] == 'GIVE' ? 'Giving Away' : 'Requesting'),
                                                    style: TextStyle(
                                                      fontSize: 12,
                                                      color: post['type'] == 'SELL'
                                                          ? Colors.green
                                                          : (post['type'] == 'GIVE' ? Colors.blue : Colors.orange),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                            // Update the price display section
                                            if (post['type'] == 'SELL' && post['price'] != null && (post['price'] is num) && (post['price'] as num) > 0)
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(
                                                  color: Colors.green.withOpacity(0.1),
                                                  borderRadius: BorderRadius.circular(12),
                                                ),
                                                child: Text(
                                                  '৳${post['price']}',
                                                  style: const TextStyle(
                                                    fontSize: 14,
                                                    fontWeight: FontWeight.bold,
                                                    color: Colors.green,
                                                  ),
                                                ),
                                              ),
                                          ],
                                        ),
                                        const SizedBox(height: 12),

                                        // Main content row with image on right and details on left
                                        Row(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            // Left side - Post details
                                            Expanded(
                                              flex: 2,
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  // Post title and author
                                                  Text(
                                                    post['title']?.toString() ?? 'No Title',
                                                    style: const TextStyle(
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 16,
                                                    ),
                                                    maxLines: 2,
                                                    overflow: TextOverflow.ellipsis,
                                                  ),
                                                  if (post['author'] != null && post['author'].toString().isNotEmpty)
                                                    Text(
                                                      'by ${post['author']}',
                                                      style: const TextStyle(
                                                        fontSize: 14,
                                                        color: Colors.grey,
                                                      ),
                                                      maxLines: 1,
                                                      overflow: TextOverflow.ellipsis,
                                                    ),
                                                  const SizedBox(height: 8),

                                                  // Post description
                                                  if (post['description']?.toString().isNotEmpty == true)
                                                    Text(
                                                      post['description'].toString(),
                                                      style: const TextStyle(fontSize: 14),
                                                      maxLines: 3,
                                                      overflow: TextOverflow.ellipsis,
                                                    ),
                                                  const SizedBox(height: 8),

                                                  // Post details (batch, department, location)
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

                                                  // Contact info
                                                  if (post['contact_number'] != null && post['contact_number'].toString().isNotEmpty)
                                                    Padding(
                                                      padding: const EdgeInsets.only(top: 8),
                                                      child: Row(
                                                        children: [
                                                          const Icon(Icons.phone, size: 14, color: Colors.grey),
                                                          const SizedBox(width: 4),
                                                          Expanded(
                                                            child: Text(
                                                              post['contact_number'].toString(),
                                                              style: const TextStyle(fontSize: 12, color: Colors.grey),
                                                              maxLines: 1,
                                                              overflow: TextOverflow.ellipsis,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                    ),

                                                  // Post date
                                                  Padding(
                                                    padding: const EdgeInsets.only(top: 8),
                                                    child: Text(
                                                      _formatDate(post['created_at']),
                                                      style: const TextStyle(fontSize: 11, color: Colors.grey),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),

                                            // Right side - Post image
                                            if (post['post_image'] != null && post['post_image'].toString().isNotEmpty)
                                              Container(
                                                width: 250,
                                                height: 220,
                                                margin: const EdgeInsets.only(left: 12),
                                                decoration: BoxDecoration(
                                                  borderRadius: BorderRadius.circular(8),
                                                  border: Border.all(color: Colors.grey.shade300),
                                                  image: DecorationImage(
                                                    image: NetworkImage('${API.hostConnect}/${post['post_image']}'),
                                                    fit: BoxFit.contain,
                                                  ),
                                                ),
                                              ),
                                          ],
                                        ),

                                        const SizedBox(height: 12),

                                        // View Image Button
                                        Align(
                                          alignment: Alignment.centerRight,
                                          child: TextButton(
                                            onPressed: () {
                                              _selectItem(post);
                                            },
                                            child: const Text('View Image →'),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                )).toList(),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Right Column - Details View (Conditional)
            if (_selectedItem != null)
              Expanded(
                flex: 1,
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.grey.withOpacity(0.3),
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(15),
                      bottomLeft: Radius.circular(15),
                    ),
                  ),
                  child: ClipRRect(
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(15),
                      bottomLeft: Radius.circular(15),
                    ),
                    child: Container(
                      width: double.infinity,
                      height: double.infinity,
                      child: _selectedItem == 'My Posts'
                          ? MyPosts(
                        user: _currentUser,
                        onClose: _clearSelection,
                        onPostChanged: _loadPosts,
                      )
                          : SingleChildScrollView(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Content based on selection (excluding My Posts)
                              if (_selectedItem == 'Update Profile')
                                UpdateProfileForm(
                                  user: _currentUser,
                                  onProfileUpdated: () {
                                    _loadUserProfile();
                                    _loadPosts();
                                  },
                                  onClose: _clearSelection,
                                )
                              else if (_selectedItem == 'Change Password')
                                ChangePasswordForm(onClose: _clearSelection)
                              else if (_selectedItem == 'Email Settings')
                                  EmailSettingsForm(onClose: _clearSelection)
                                else if (_selectedItem == 'Create Post')
                                    CreatePostForm(
                                      onClose: _clearSelection,
                                      onPostCreated: () {
                                        _clearSelection();
                                        _loadPosts();
                                      },
                                    )
                                  else if (_selectedItem is Map)
                                      SizedBox(
                                        height: MediaQuery.of(context).size.height - 200,
                                        child: PostDetail(
                                          user: _currentUser,
                                          onClose: _clearSelection,
                                          post: _selectedItem,
                                        ),
                                      )
                                    else
                                      Center(
                                        child: Text(
                                          'Details for $_selectedItem',
                                          style: const TextStyle(fontSize: 16),
                                        ),
                                      ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}