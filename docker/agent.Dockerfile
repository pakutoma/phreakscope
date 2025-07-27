FROM golang:1.23-alpine AS builder

# Set working directory
WORKDIR /app

# Copy go module files
COPY cmd/agent/go.mod cmd/agent/go.sum ./

# Download dependencies
RUN go mod download

# Copy source
COPY cmd/agent/ ./

# Build static binary
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o phreakscope-agent .

# Final stage
FROM alpine:latest

# Install ca-certificates for HTTPS requests
RUN apk --no-cache add ca-certificates

# Create non-root user
RUN addgroup -g 1001 phreakscope && \
    adduser -D -u 1001 -G phreakscope phreakscope

# Copy binary
COPY --from=builder /app/phreakscope-agent /usr/local/bin/phreakscope-agent

# Set permissions
RUN chmod +x /usr/local/bin/phreakscope-agent

# Switch to non-root user
USER phreakscope

# Default command
CMD ["/usr/local/bin/phreakscope-agent"]