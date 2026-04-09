Objective:  Refactor Client/Studio Photographer/Owner Booking Workflow (Post-Availment)

    1). Current Issue:
        In the existing client booking workflow, both the studio photographer and owner are able to mark a booking as completed even if the client’s online gallery has not yet been uploaded.

    2). Proposed Enhancement:
        Introduce a validation step that requires image upload to the client’s online gallery before allowing the booking to be marked as completed.

    3). Expected Behavior After Revision:
        - The “Mark as Completed” action should be disabled or restricted until images are successfully uploaded.

        - The system should verify the presence of uploaded gallery content before permitting status updates.

        - Once the gallery upload is confirmed, the studio photographer or owner can proceed to mark the booking as completed.

    4). Goal:
        Ensure a more consistent and reliable workflow by enforcing that deliverables (online gallery images) are completed prior to closing a booking, improving accountability and client satisfaction.